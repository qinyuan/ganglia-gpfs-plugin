#!/usr/bin/perl

use strict;
use warnings;
use Socket;

# Setup the socket to be used 255 is IPPROTO_RAW)
socket(RAW, AF_INET, SOCK_RAW, 255) or die $!;
setsockopt(RAW, 0, 1, 1);

# Builds an IP header (Layer 3)
sub ip_header($$) {
    my $ip_src = shift;
    my $ip_dst = shift;
    my $ip_ver         = 4;                    # IP Version 4            (4 bits)
    my $ip_header_len  = 5;                    # IP Header Length        (4 bits)
    my $ip_tos         = 0;                    # Differentiated Services (8 bits)
    my $ip_total_len   = $ip_header_len + 20;  # IP Header Length + Data (16 bits)
    my $ip_frag_id     = 0;                    # Identification Field    (16 bits)
    my $ip_frag_flag   = '000';                # IP Frag Flags (R DF MF) (3 bits)
    my $ip_frag_offset = '0000000000000';      # IP Fragment Offset      (13 bits)
    my $ip_ttl         = 255;                  # IP TTL                  (8 bits)
    my $ip_proto       = 17;                   # IP Protocol             (8 bits)
    my $ip_checksum    = 0;                    # IP Checksum             (16 bits)

    my $ip_header = pack(
        'H2 H2 n n B16 h2 c n a4 a4',
        $ip_ver . $ip_header_len, $ip_tos, $ip_total_len,
        $ip_frag_id, $ip_frag_flag . $ip_frag_offset,
        $ip_ttl, $ip_proto, $ip_checksum,
        $ip_src,
        $ip_dst
    );

    return $ip_header;
}

# Builds a UDP header (Layer 4)
sub udp_header($$$) {
    my $udp_src_port = shift;
    my $udp_dst_port = shift;
    my $payload = shift;
    my $udp_len      = 8 + length($payload);  # UDP Length              (16 bits)
    my $udp_checksum = 0;                      # UDP Checksum            (16 bits)

    my $udp_header = pack(
        'n n n n',
        $udp_src_port, $udp_dst_port,
        $udp_len, $udp_checksum
    );

    return $udp_header;
}

# Builds a data section
sub payload($) {
    my $data = shift;
    # Pack the data in dynamically
    my $payload = pack(
        'a' . length($data), $data
    );
    return $payload;
}

# Send the packet
sub send_packet($$) {
    my $packet = shift; 
    my $ip_dst = shift;
    # @_ doesn't work, you need to use $_[0] as the param to the send sub!
    send(RAW, $packet, 0, pack('Sna4x8', AF_INET, 60, $ip_dst));
}

unless ($ARGV[0]) {
    print "Usage: $0 DESTINATION_IP\n";
    exit 1;
}
sub main {
    # Source and destination IP/Hostname
    my $ip_dst = (gethostbyname($ARGV[0]))[4];
    my $udp_src_port = 9649;                   # UDP Sort Port           (16 bits)
    my $udp_dst_port = 9649;                   # UDP Dest Port           (16 btis)

    # host name array
    my $cluster = 'gpfs_virtual';
    my @hosts = map {"vhost$_"} (1..20);
    my @filesystems = map {"vfs$_"} (1..5);
    
    for my $host (@hosts) {
        my $data = "ident:2:$host:20140117:$cluster.$host:3.4.0.27\n";
        $data .= "state:active:1\n";
        $data .= "verbs:::\n";
        for (@filesystems) {
            $data .= "fs:$_:/{$_}home:mounted:ok::\n";
        }
        my $timestamp = time();
        $data .= "end:$timestamp:$timestamp\n";

        my $payload = payload($data);
        $host =~ s/\D+/192.168.9.1/g;
        my $ip_src = (gethostbyname($host))[4];
        my $packet = ip_header($ip_src, $ip_dst);
        $packet .= udp_header($udp_src_port, $udp_dst_port, $payload);
        # Add in a data section
        $packet .= $payload;
        send_packet($packet, $ip_dst);
    }
}

while (1) {
    main();
    sleep 10;
}
