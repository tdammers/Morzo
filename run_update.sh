#!/usr/bin/env bash
cd `dirname $0`
LOGFILE=logs/update.log
date >> "$LOGFILE"
php -f ./run_update.php >> "$LOGFILE"
