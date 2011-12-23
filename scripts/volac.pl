#!/usr/bin/perl

use strict;
use warnings;

use utf8;
use Daemon::Generic;
use Log::Log4perl qw(:easy);
use YAML::Tiny;
use DBI;
use Time::HiRes;
use DateTime;
use Net::Twitter::Lite;
use Data::Dumper;
use Encode;
use URI;
use JSON::XS;
use LWP::UserAgent;
use HTTP::Cookies;
use HTTP::Request::Common qw/POST/;
use XML::Simple;
use Digest::MD5 qw/md5_hex/;
use Math::BigInt;

$|++;

my $API_SECRET = "7baa09b8cec43a12ae4d17f6c1757dec";
my $DB      = 'AVTOYAMBA';
my $DB_USER = 'root';
my $DB_PASS = 'rjirftcn';

newdaemon(
    progname        => 'volac',
    pidfile         => '/var/run/volac.pid',
    configfile      => './ay.config.yml',
);

sub gd_postconfig {
    INFO "Volac started!";
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
        file  => '>>/var/log/volac.log'
    });

    # DB connect
    my $dbl = DBI->connect(
        'DBI:mysql:'.$self->{'conf'}{'db'}{'name'}.':localhost',
        $self->{'conf'}{'db'}{'user'}, 
        $self->{'conf'}{'db'}{'pass'});

    $dbl->do("SET NAMES 'utf8'");
    $self->{dbc} = $dbl;

    my $client = Net::Twitter::Lite->new(
        consumer_key        => $self->{'conf'}{'twitter'}{'consumer_key'},
        consumer_secret     => $self->{'conf'}{'twitter'}{'consumer_secret'},
        access_token        => $self->{'conf'}{'twitter'}{'access_token'},
        access_token_secret => $self->{'conf'}{'twitter'}{'access_token_secret'},
    );

    $self->{tc} = $client;

    return ();
}

sub gd_run {
    my ($self) = @_;

    while (42) {
        sleep(10);

        # Get last mention tweet id, that we have
        my $last_id = getLastMentionId($self->{'dbc'});

        # Get last mentions
        my $st = Time::HiRes::time();

        my $tweets = $self->{'tc'}->mentions({
            since_id         => $last_id,
            count            => 200,
            include_rts      => 0,
            include_entities => 1,
        });

        if (scalar(@$tweets) > 0) {
            my $duration = sprintf("%.2f", Time::HiRes::time() - $st);
            INFO "Get ".scalar(@$tweets)." new tweets ($duration sec)\n";

            # Process tweets
            $st = Time::HiRes::time();
            my $max_id = processTweets($tweets, $last_id, $self->{'conf'});

            $duration = sprintf("%.2f", Time::HiRes::time() - $st);
            INFO "Processing duration per ".scalar(@$tweets)." tweets: ".sprintf($duration)." sec\n";

            saveLastMentionId($self->{'dbc'}, $max_id);
        }
    }
}

sub findNumberInHash {
    my ($hashtags) = @_;

    return if (!$hashtags || scalar(@$hashtags) < 1);

    foreach my $h (@$hashtags) {
        my $text = $h->{'text'}; 
        $text = decode_utf8($text);

        if ($text =~ /^[a-zа-яA-ZА-Я]\d{3}[a-zа-яA-ZА-Я]{2}\d+$/) {
            return $text;
        }
    }

    return;
}

sub extractImage {
    my ($media) = @_;

    return if (!$media || scalar(@$media) < 1);

    foreach my $m (@$media) {
        next if ($m->{'type'} ne 'photo');

        return $m->{'media_url'};
    }

    return undef;
}

sub postMessage {
    my ($m, $conf) = @_;
    
    INFO "\t\t* Post tweet via API...";
    my $st = Time::HiRes::time();

    my $ua = LWP::UserAgent->new();
    $ua->timeout(40);

    my $req = POST $conf->{'api'}{'url'}{'post'}, $m;
    $req->authorization_basic('api', $API_SECRET);

    my $res = $ua->request($req);
    my $duration = sprintf("%.2f", Time::HiRes::time() - $st);

    if ($res->is_success) {
        my $ans = decode_json( $res->content() );
        if ($ans && $ans->{'post'} eq 'done') {
            INFO "\t\tPost result: OK; $duration sec\n";
            return 1;
        } else {
            ERROR "\t\tPost result: FAIL; ".$res->content()."\n";

            return 0;
        }

    } else {
        ERROR "\t\tPost result: ERROR (".$res->status_line.");\n";
        return;
    }
}

sub processTweets {
    my ($tweets, $max_id, $conf) = @_;

    INFO "Begin process ".scalar(@$tweets)." tweets\n";
    $max_id = Math::BigInt->new($max_id);

    foreach my $t (@$tweets) {
        my $tweet_id = $t->{'id'};
        my $htags    = $t->{'entities'}{'hashtags'};
        INFO "\tProcess $tweet_id"; 

        my $tweet_id_bi = Math::BigInt->new($tweet_id);
        $max_id = $tweet_id_bi if ($tweet_id_bi > $max_id);

        if ($t->{'user'}{'id'} eq $conf->{'twitter'}{'ay_user_id'}) {
            # AvtoYamba don't post AvtoYamba :-)
            ERROR "\tResult: FAIL - avtoyamba tweat!";
            next;
        }

        my $number = findNumberInHash($htags);

        if ($number) {
            # wheee! we found tweet with number!
            # post in DB
            INFO "\tRefult: OK, number '".encode_utf8($number)."'\n";

            my $media = $t->{'entities'}{'media'};
            my $img   = extractImage($media);
            
            my $text  = encode('utf-8', $t->{'text'});
            utf8::decode($text);

            my $message = {
                number    => $number,
                user_name => '@'.$t->{'user'}{'screen_name'},
                text      => $text,
                tid       => $tweet_id,
            };

            $message->{'image'} = $img if (defined($img));

            postMessage($message, $conf);

        } else {
            ERROR "\tResult: FAIL - number not found!";
        }
    }

    return $max_id;
}

sub getLastMentionId {
    my ($dl) = @_;

    my $query = "select value from Config where parameter = ?;";
    my $eq = $dl->prepare($query);
    my $rows = $eq->execute('mention_id');

    my @n = $eq->fetchrow_array();

    return $n[0];
}

sub saveLastMentionId {
    my ($dl, $max) = @_;

    my $query = "update Config set value = ? where parameter = ?;";
    my $eq = $dl->prepare($query);
    my $rows = $eq->execute($max, 'mention_id');

    INFO "Last metion id updated: $max\n";

    return 1;
}

