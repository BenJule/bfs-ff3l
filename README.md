## Introduction

bfs is a small collection of tools for monitoring your server's network interface using iptables.

It saves the data in RRD and/or in a CSV file.

The frontent can be seen here: http://stats.bitleader.com/ (this is also the git version).

## Prerequisites
* a *NIX system 
* bash
* iptables
* a webserver with PHP +5.3
* for the RRD database: the php-rrd module and rrdtool

## Installation Instructions

* On your linux server:
```
git clone https://github.com/BenJule/bfs-ff3l.git /opt/bfs-ff3l
cp /opt/bfs-ff3l/conf/bfs.conf.example /opt/bfs-ff3l/conf/bfs-ff3l.conf
nano /opt/bfs-ff3l/conf/bfs-ff3l.conf #Edit the configuration file
```
* Modify your firewall (and apply it), so that all the rules that require accounting have the comment starting with the same string as the one specified in `bfs-ff3l.conf` (the variable `STATS_COMMENT_PREFIX`).
* If you need a firewall script example, you can use `/opt/bfs-ff3l/bin/start_firewall.sh` (*recommended*, since it uses the same variables as the `generate_statistics.sh` script).
* Link the `/opt/bfs-ff3l/www` folder somewhere in your DocumentRoot folder
* Test it! `/opt/bfs-ff3l/bin/generate_statistics.sh`
* Copy the cronjob (`/opt/bfs-ff3l/cron.d/bfs_cron`) to `/etc/cron.d/`

## Notes
* The first word in the rules comment _after_ `${STATS_COMMENT_PREFIX}` will be used as RRD/CSV file name.
* The same string is the one shown on the graph for the metric.

Special thanks to https://github.com/tlex for Support his Script
Patches and/or comments are welcomed!

