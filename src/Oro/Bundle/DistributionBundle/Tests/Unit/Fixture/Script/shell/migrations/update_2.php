<?php

$opts = getopt('p:');
system(sprintf('%s -r "echo \'update 2 via shell\';"', $opts['p']));
