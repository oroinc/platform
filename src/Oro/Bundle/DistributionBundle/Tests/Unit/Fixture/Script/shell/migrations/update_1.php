<?php

$opts = getopt('p:');
system(sprintf('%s -r "echo \'update 1 via shell\';"', $opts['p']));
