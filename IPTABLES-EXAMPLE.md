This is an output example for `iptables -nL --line-numbers`. All the "STS [...]" comments are parsed by the `generate_statistics.sh` script, since they are defined as such in the file bfs.conf.

```
[root@vserver01]-[ ~ ] # iptables -nL --line-numbers
Chain INPUT (policy ACCEPT)
num  target     prot opt source               destination
1    ACCEPT     all  --  0.0.0.0/0            0.0.0.0/0
2    DSTATS     all  --  0.0.0.0/0            0.0.0.0/0            state INVALID /* DROP: Standard flags */
3    DSTATS     all  --  188.240.48.5         0.0.0.0/0            /* DROP: Package on public interface with source local IP */
4    STATS      icmp --  0.0.0.0/0            0.0.0.0/0            icmptype 8 limit: avg 1/sec burst 5 /* Allow: ICMP Requests - 1 per second */
5    STATS      icmp --  0.0.0.0/0            0.0.0.0/0            icmptype 0 limit: avg 1/sec burst 5 /* Allow: ICMP Replies - 1 per second */
6    DSTATS     icmp --  0.0.0.0/0            0.0.0.0/0            /* DROP: ICMP Flood */
7    STATS      tcp  --  0.0.0.0/0            0.0.0.0/0            state RELATED,ESTABLISHED /* GenStat */
8    STATS      udp  --  0.0.0.0/0            0.0.0.0/0            state RELATED,ESTABLISHED /* GenStat */
9    LOGGING    tcp  --  0.0.0.0/0            0.0.0.0/0            tcp flags:0x3F/0x17 /* LOG: Reject invalid packets */
10   LOGGING    tcp  --  0.0.0.0/0            0.0.0.0/0            tcp flags:0x03/0x03 /* LOG: Reject invalid packets */
11   LOGGING    tcp  --  0.0.0.0/0            0.0.0.0/0            tcp flags:0x06/0x06 /* LOG: Reject invalid packets */
12   LOGGING    tcp  --  0.0.0.0/0            0.0.0.0/0            tcp flags:!0x17/0x02 state NEW /* LOG: Make sure new incoming connections are SYN packets */
13   LOGGING    tcp  --  0.0.0.0/0            0.0.0.0/0            tcp flags:0x3F/0x3F /* LOG: Reject malformed xmas packets */
14   LOGGING    tcp  --  0.0.0.0/0            0.0.0.0/0            tcp flags:0x3F/0x00 /* LOG: Reject malformed null packets */
15   DSTATS     tcp  --  0.0.0.0/0           !188.240.48.5         /* DROP: TCP Package not for us */
16   DSTATS     udp  --  0.0.0.0/0           !188.240.48.5         /* DROP: UDP Package not for us */
17   STATS      tcp  --  0.0.0.0/0            0.0.0.0/0            tcp dpt:22 /* GenStat: SSH */
18   STATS      tcp  --  0.0.0.0/0            0.0.0.0/0            tcp dpt:443 /* GenStat: HTTPS */
19   STATS      tcp  --  0.0.0.0/0            0.0.0.0/0            tcp dpt:80 /* GenStat: HTTP */
20   STATS      tcp  --  0.0.0.0/0            0.0.0.0/0            state NEW tcp dpt:32443 /* GenStat: Plex */
21   STATS      tcp  --  0.0.0.0/0            0.0.0.0/0            state NEW tcp dpt:32400 /* GenStat: Plex */
22   STATS      udp  --  0.0.0.0/0            0.0.0.0/0            state NEW udp dpt:32400 /* GenStat: Plex */
23   STATS      udp  --  0.0.0.0/0            0.0.0.0/0            state NEW udp dpt:32410 /* GenStat: Plex */
24   STATS      udp  --  0.0.0.0/0            0.0.0.0/0            state NEW udp dpt:32412 /* GenStat: Plex */
25   STATS      udp  --  0.0.0.0/0            0.0.0.0/0            state NEW udp dpt:32414 /* GenStat: Plex */
31   DSTATS     udp  --  0.0.0.0/0            0.0.0.0/0            udp dpt:137 /* SMB Protocol */
32   DSTATS     udp  --  0.0.0.0/0            0.0.0.0/0            udp dpt:138 /* SMB Protocol */
33   DSTATS     udp  --  0.0.0.0/0            0.0.0.0/0            udp dpt:67 /* DHCP */
34   DSTATS     udp  --  0.0.0.0/0            0.0.0.0/0            udp dpt:1900 /* Microsoft SSDP Enables discovery of UPnP devices */
35   DSTATS     all  --  0.0.0.0/0            224.0.0.0/24         /* Multicast */
36   DSTATS     udp  --  0.0.0.0/0            0.0.0.0/0            /* DROP: Rest UDP */
37   LOGGING    all  --  0.0.0.0/0            0.0.0.0/0            /* LOG: Reject TCP */

Chain FORWARD (policy ACCEPT)
num  target     prot opt source               destination

Chain OUTPUT (policy ACCEPT)
num  target     prot opt source               destination
1    ACCEPT     all  --  0.0.0.0/0            0.0.0.0/0
2    STATS      all  --  0.0.0.0/0            0.0.0.0/0            /* GenStat */

Chain DSTATS (12 references)
num  target     prot opt source               destination
1    DROP       all  --  0.0.0.0/0            0.0.0.0/0            /* STS 1-DROP */

Chain LOGGING (7 references)
num  target     prot opt source               destination
1    LOG        all  --  0.0.0.0/0            0.0.0.0/0            limit: avg 2/min burst 5 /* Rejected packages are logged */ LOG flags 0 level 4 prefix "BFS Reject: "
2    REJECT     all  --  0.0.0.0/0            0.0.0.0/0            /* STS 2-REJECT */ reject-with icmp-port-unreachable

Chain STATS (19 references)
num  target     prot opt source               destination
1    ACCEPT     all  --  188.240.48.5         0.0.0.0/0            /* STS 3-UPLOAD Source Local Destination Alien */
2    ACCEPT     all  --  0.0.0.0/0            188.240.48.5         /* STS 4-DOWNLOAD Source Alien Destination Local */
3    LOG        all  --  0.0.0.0/0            0.0.0.0/0            /* LOG: packages which are not supposed to be here */ LOG flags 0 level 4 prefix "BFS Stats: "
4    DSTATS     all  --  0.0.0.0/0            0.0.0.0/0            /* DROP: Source Alien Destination Alien */
```
