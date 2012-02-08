#!/bin/bash
java -cp "./build:./lib/*" -Xmx512M org.deri.eurostat.toc.DiffToC -i "/data/eurostat/toc/table_of_contents.xml" -o "/data/eurostat/toc/" -f "/data/eurostat/update-logs/" -z "/data/eurostat/temp-zip/" -t "/data/eurostat/temp-data/" -s "/data/eurostat/data/" -d "/data/eurostat/dsd/" -l "/data/eurostat/logs/" -p "/data/eurostat/original-data/" -r "/data/eurostat/raw-data/" -b "/data/eurostat/tsv/" -v "/data/eurostat/temp-tsv/"
