#!/usr/bin/env bash
step=5 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do

    curl 'http://videoline.bogokj.com/mapi/public/index.php/api/crontab_api/service_see_hi_crontab'

    sleep $step

done