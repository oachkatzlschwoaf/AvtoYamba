#!/usr/bin/perl

use strict;
use warnings;

use Daemon::Generic;
use DBI;
use Encode;
use Data::Dumper;
use Log::Log4perl qw(:easy);

use Time::HiRes;
use LWP::UserAgent;
use MIME::Lite; 
use Template;
use HTTP::Cookies;
use URI;
use JSON::XS;
use YAML::Tiny;


$|++;

newdaemon(
    progname        => 'decarabia',
    pidfile         => '/var/log/decarabia.pid',
    configfile      => './ay.config.yml',
);

sub gd_postconfig {
    INFO "Decarabia started!";
    INFO "======================================";
}

sub gd_preconfig {
    my ($self) = @_;

    # Read config
    my $yaml = YAML::Tiny->read( $self->{'configfile'} );
    $self->{'conf'} = $yaml->[0];

    # Logger
    Log::Log4perl->easy_init({
        level => $DEBUG,
        file  => '>>/var/log/decarabia.log'
    });

    # DB connect
    my $dbl = DBI->connect(
        'DBI:mysql:'.$self->{'conf'}{'db'}{'name'}.':localhost',
        $self->{'conf'}{'db'}{'user'}, 
        $self->{'conf'}{'db'}{'pass'});

    $dbl->do("SET NAMES 'utf8'");
    $self->{dbc} = $dbl;

    return ();
}

sub gd_run {
    my ($self) = @_;

    while (42) {
        sleep(1);

        my ($message, $notify) = 
            getNewMessages($self->{'dbc'});

        if ($message && scalar(keys %$message) > 0) {
            my $ss = getSubscribers($message, $self->{'dbc'});
            sendNotify($message, $ss, $self->{'dbc'}, $self->{'conf'}); 
        }
    }
}

sub getNewMessages {
    my ($d) = @_;

    # Get notifies
    my $query = "select id, message_id, tweet_done, notify_done, created_at from Notify where notify_done = ? or notify_done is null;";
    my $eq = $d->prepare($query);
    my $rows = $eq->execute(0);

    $rows = 0 if ($rows eq '0E0');
    return if ($rows == 0);
    INFO "Get $rows new notifies\n";

    my @notify;
    my %message_id;
    while (my @n = $eq->fetchrow_array) {
        push(@notify, $n[0]);
        $message_id{ $n[1] } = $n[0];
    }

    # Get messages
    my $ph = substr("?, " x scalar(keys %message_id), 0, -2);
    $query = "select id, number, user_name, text, image_tmp from Message where ID in ($ph)";
    $eq = $d->prepare($query);
    $rows = $eq->execute(keys %message_id);

    $rows = 0 if ($rows eq '0E0');

    my %message;
    my %number;
    while (my @m = $eq->fetchrow_array) {
        push(@{ $message{ $m[1] } }, {
            'id'        => $m[0], 
            'nid'       => $message_id{ $m[0] },
            'author'    => $m[2], 
            'text'      => $m[3],
            'number'    => $m[1],
            'image_tmp' => $m[4],
        });

        $number{ $m[1] } = 1;
    }

    INFO "Get $rows messages for ".scalar(keys %number)." numbers\n";

    return (\%message, \@notify);
}


sub getSubscribers {
    my ($messages, $d) = @_;

    my $ph = substr("?, " x scalar(keys %$messages), 0, -2);
    my $query = "select id, number, email, phone, unix_timestamp(created_at) from Subscribe where Number in ($ph)";
    my $eq = $d->prepare($query);
    my $rows = $eq->execute(keys %$messages);

    $rows = 0 if ($rows eq '0E0');

    my %subscribe;
    my %secret_code;
    my $phones = 0;
    my $emails = 0;

    while (my @s = $eq->fetchrow_array) {
        if ($s[2]) {
            $subscribe{ $s[1] }{'emails'}{ $s[2] }{'u_t'} = $s[4]; 
            $subscribe{ $s[1] }{'emails'}{ $s[2] }{'id'}  = $s[0]; 
            $emails++; 
        }

        if ($s[3]) {
            $subscribe{ $s[1] }{'phones'}{ $s[3] }{'u_t'} = $s[4]; 
            $subscribe{ $s[1] }{'phones'}{ $s[3] }{'id'}  = $s[0]; 
            $phones++;
        }
    }

    INFO "Get $rows subscribers (emails: $emails, phones: $phones)\n"; 

    return \%subscribe;
}


sub sendNotify {
    my ($messages, $ss, $dbc, $config) = @_;

    INFO "Send notify";

    foreach my $number (keys %$messages) {
        my $messages_by_number = $messages->{$number};

        my $emails = $ss->{$number}{'emails'} || {};
        my $phones = $ss->{$number}{'phones'} || {};

        foreach my $m (@$messages_by_number) {
            my $st = Time::HiRes::time();

            if ($emails && scalar(keys %$emails) > 0) {
                sendEmail($emails, $m, $number, $config);
            }

            if ($phones && scalar(keys %$phones) > 0) {
                sendSms($phones, $m, $number, $config);
            }

            markNotify($m->{'nid'}, $dbc);

            my $duration = sprintf("%.2f", Time::HiRes::time() - $st);
            INFO "\tDuration: $duration sec";
        }
    }

}

sub sendEmail {
    my ($emails, $message, $number, $config) = @_;

    INFO "\t* Send ".scalar(keys %$emails)." emails for message ".$message->{'id'}.", number: '$number'";

    # Compose email
    my $tconf = {
        INCLUDE_PATH => $config->{'tmpl_path'}, 
    };

    foreach my $email (keys %$emails) {
        INFO "\t- Send email to $email";
        my $st = Time::HiRes::time();

        my $ut = $emails->{$email}{'u_t'};
        my $id = $emails->{$email}{'id'};
        my $code = makeSecretCode($id, $ut);

        my $tt = Template->new($tconf);

        my $vars = {
            number   => $number,
            message  => $message,
            code     => $code,
        };

        my $out = '';
        $tt->process('email.tt2', $vars, \$out)
            || die $tt->error();

        my $msg = MIME::Lite->new(
            From    => $config->{'mail'}{'from'},
            To      => $email,
            Subject => "Новое сообщение о номере $number",
            Data    => $out
        );

        my $res = $msg->send(
            'smtp', 
            $config->{'mail'}{'server'}, 
            AuthUser => $config->{'mail'}{'user'}, 
            AuthPass => $config->{'mail'}{'pass'});

        my $duration = sprintf("%.2f", Time::HiRes::time() - $st);

        if ($res) {
            INFO "\tSend result: OK ($duration sec)";
        } else {
            ERROR "\tSend result: FAIL";
        }
    }

    return 1;
}

sub sendSms {
    my ($phones, $message, $number, $config) = @_;

    INFO "\t* Begin send ".scalar(keys %$phones)." sms for message ".$message->{'id'}.", number: '$number'";

    # Authorize
    # non-optiomal (try initialize in preconfig)
    my $st = Time::HiRes::time();

    my $url = $config->{'sms'}{'api_url'};

    my $cookie_jar = HTTP::Cookies->new(file => "tmp/lwp_cookies.dat", autosave => 1);

    my $ua = LWP::UserAgent->new();
    $ua->timeout(10);
    $ua->cookie_jar($cookie_jar);

    my $qp = { 
        'method'   => 'login',
        'format'   => 'json',
        'email'    => $config->{'sms'}{'email'},
        'password' => $config->{'sms'}{'pass'} 
    };

    my $u = URI->new($url);
    $u->query_form( $qp );

    my $res = $ua->get($u);
    
    if ($res->is_success) {
        my $ans = decode_json($res->content);

        my $sid = $ans->{'response'}{'data'}{'sid'};
        INFO "\tSMS API Auth Status: OK (session: $sid)";

        my $text = $message->{'text'};

        my $sms_text = "Сообщение о '$number': '$text', читайте на http://avtoyamba.com/$number";

        foreach my $phone (keys %$phones) {
            # Send sms
            INFO "\t- Send sms to $phone";

            my $push_res = $ua->post(
                $url, 
                [ 'method' => 'push_msg',
                  'phone'  => '7'.$phone,
                  'format' => 'json',
                  'text'   => $sms_text ]
            );

            my $duration = sprintf("%.2f", Time::HiRes::time() - $st);

            if ($res->is_success) {
                INFO "\tSend result: OK ($duration sec)";
            } else {
                ERROR "\tSend result: FAIL";
            }
        }
        
    } else {
        ERROR "\tSMS API Auth Status: FAIL";
        return;
    }

    return 1;
}

sub markNotify {
    my ($notify_id, $d) = @_;

    INFO "\t- Mark notify $notify_id";

    my $query = "update Notify set notify_done = ? where id = ?";
    my $eq = $d->prepare($query);
    my $rows = $eq->execute(1, $notify_id);

    $rows = 0 if ($rows eq '0E0');
    INFO "\tMark result: OK\n" if ($rows > 0);
    ERROR "\tMark result: FAIL\n" if ($rows == 0);
     
    return 1;
}

sub makeSecretCode {
    my ($id, $ut) = @_;

    my $str;

    my $l  = length($id);

    my @id_arr = split(//, $id);
    my $id_sum = 0;
    map { $id_sum += $_ } @id_arr;

    $str = getRandString( 5, 5 + int(rand(5)) ); 
    $str .= getRandString( 5, 5 + int(rand(5)) ); 

    my $k = substr($str, $id_sum, 1); 
    $k = 2 if ($k < 2);
    $k = 5 if ($k > 5);
    $k *= -1;

    $str .= $l . $id; 

    my $ut_part = substr($ut, $k);
    my @ut_part_arr = split(//, $ut_part);
    my $ut_part_sum = 0;
    map { $ut_part_sum += $_ } @ut_part_arr;

    $str .= $ut_part_sum;

    $str .= getRandString( 5, 5 + int(rand(5)) ); 

    return $str;
}

sub getRandString {
    my ($from, $to) = @_;

    my $iter = int(rand($to - $from + 1)) + $from; 
    my $ret = $iter;

    for (my $i = 0; $i < $iter; $i++) { 
        my $r = int(rand(10));
        $ret .= $r;
    }
    
    return $ret;
}

