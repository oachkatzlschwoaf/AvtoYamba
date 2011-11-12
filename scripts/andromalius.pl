#!/usr/bin/perl

use strict;
use warnings;

use Daemon::Generic;
use DBI;
use Encode;
use Data::Dumper;
use Log::Log4perl qw(:easy);
use Net::Twitter;
use Scalar::Util 'blessed';
use LWP::UserAgent;
use HTTP::Request::Common qw/POST/;
use YAML::Tiny;
use Time::HiRes;
use JSON::XS;

$|++;

newdaemon(
    progname        => 'andromalius',
    pidfile         => '/var/run/andromalius.pid',
    configfile      => './ay.config.yml',
);

sub gd_postconfig {
    INFO "Andromalius started!";
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
        file  => '>>/var/log/andromalius.log'
    });

    # DB connect
    my $dbl = DBI->connect(
        'DBI:mysql:'.$self->{'conf'}{'db'}{'name'}.':localhost',
        $self->{'conf'}{'db'}{'user'}, 
        $self->{'conf'}{'db'}{'pass'});

    $dbl->do("SET NAMES 'utf8'");
    $self->{dbc} = $dbl;

    # Twitter connect
    my $nt = Net::Twitter->new(
        traits              => [qw/OAuth API::REST/],
        consumer_key        => $self->{'conf'}{'twitter'}{'consumer_key'},
        consumer_secret     => $self->{'conf'}{'twitter'}{'consumer_secret'},
        access_token        => $self->{'conf'}{'twitter'}{'access_token'},
        access_token_secret => $self->{'conf'}{'twitter'}{'access_token_secret'},
    );

    $self->{nt} = $nt;

    return ();
}

sub gd_run {
    my ($self) = @_;

    while (42) {
        sleep(1);

        my ($message, $notify) = 
            getNewMessages($self->{'dbc'});

        if ($message && scalar(keys %$message) > 0) {
            tweatMessages($message, $self->{'nt'}, $self->{'conf'}, $self->{'dbc'});
        }
    }
}

sub getNewMessages {
    my ($d) = @_;

    # Get notifies
    my $query = "select * from Notify where tweet_done = ? or tweet_done is null;";
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
    $query = "select * from Message where ID in ($ph)";
    $eq = $d->prepare($query);
    $rows = $eq->execute(keys %message_id);

    $rows = 0 if ($rows eq '0E0');

    my %message;
    my %number;
    while (my @m = $eq->fetchrow_array) {
        push(@{ $message{ $m[1] } }, {
            'id'        => $m[0], 
            'nid'       => $message_id{ $m[0] },
            'author'    => $m[3], 
            'text'      => $m[4],
            'number'    => $m[1],
            'image_tmp' => $m[6],
        });

        $number{ $m[1] } = 1;
    }

    INFO "Get $rows messages for ".scalar(keys %number)." numbers\n";

    return (\%message, \@notify);
}

sub tweatMessages {
    my ($messages, $nt, $conf, $dbc) = @_;

    INFO "Post tweets";

    foreach my $number (keys %$messages) {
        my $messages_by_number = $messages->{$number};

        foreach my $m (@$messages_by_number) {

            my $tweat = '@avtoyamba #'.$number.' '.$m->{'text'}.' // '.$m->{'author'};
            $tweat = substr($tweat, 0, 140);

            INFO "\t* Post tweat (mid: ".$m->{'id'}."): '$tweat'";
            my $st = Time::HiRes::time();

            # Post tweet
            my $answer;
            eval {
                if ($m->{'image_tmp'}) {
                    $answer = $nt->update_with_media($tweat, [ $m->{'image_tmp'} ]);
                } else {
                    $tweat = decode('utf8', $tweat);
                    $answer = $nt->update($tweat);
                }
            };

            my $duration = sprintf("%.2f", Time::HiRes::time() - $st);

            # Process answer
            if ( my $err = $@ ) {
                # Error
                ERROR "\tPost: FAIL! ";
                eval {
                    unless (blessed $err && $err->isa('Net::Twitter::Error')) {
                        ERROR "\tError: $err";
                        die;
                    }

                    ERROR "\tError: ".$err->error." Code: ".$err->code().", Message: ".$err->message;
                };

            } else {
                # Success: update message in DB
                INFO "\tPost: OK! ($duration sec)";

                my $update = {
                    'id'       => $m->{'id'},
                    'tweet_id' => $answer->{'id'},
                };

                if ($m->{'image_tmp'}) {
                    my $img = $answer->{'entities'}{'media'}[0]{'media_url'};
                    $update->{'image'} = $img if ($img);
                }

                updateMessage($update, $conf);
            }

            markNotify($m->{'nid'}, $dbc);
        }
    }

    return;
}

sub updateMessage {
    my ($upd, $conf) = @_;

    INFO "\t- Update message ".$upd->{'id'};
    my $st = Time::HiRes::time();

    my $ua = LWP::UserAgent->new();
    $ua->timeout(10);

    my $req = POST $conf->{'api'}{'url'}{'update'}, $upd;
    $req->authorization_basic(
        $conf->{'api'}{'login'}, 
        $conf->{'api'}{'pass'}
    );

    my $res = $ua->request($req);

    my $duration = sprintf("%.2f", Time::HiRes::time() - $st);

    if ($res->is_success) {
        my $ans = decode_json( $res->content() );
        if ($ans && $ans->{'update'} eq 'done') {
            INFO "\tUpdate result: OK! ($duration sec)";
            return 1;
        } else {
            ERROR "\tUpdate result: FAIL! ".$res->content();

            return 0;
        }

    } else {
        INFO "\tUpdate result: ERROR! ".$res->status_line;
        return;
    }
}

sub markNotify {
    my ($notify_id, $d) = @_;

    INFO "\t- Mark notify $notify_id";

    my $query = "update Notify set tweet_done = ? where id = ?";
    my $eq = $d->prepare($query);
    my $rows = $eq->execute(1, $notify_id);

    $rows = 0 if ($rows eq '0E0');
    INFO "Mark result: OK\n" if ($rows > 0);
    ERROR "Mark result: FAIL\n" if ($rows == 0);
     
    return 1;
}

