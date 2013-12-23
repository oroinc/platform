<?php

$opts = getopt('p:');
system(sprintf('%s -r "echo \'This install code was run over shell\';"', $opts['p']));
