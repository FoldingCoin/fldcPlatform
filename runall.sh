#!/bin/bash

/usr/bin/php /home/fldcPlatform/mergedFolding/bin/snapshot.php
/usr/bin/sleep 60
/usr/bin/php /home/fldcPlatform/mergedFolding/bin/calculate.php
/usr/bin/sleep 60
/usr/bin/php /home/fldcPlatform/mergedFolding/bin/stats.php
