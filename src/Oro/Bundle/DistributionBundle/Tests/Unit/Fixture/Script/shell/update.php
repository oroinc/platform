<?php

$opts = getopt('p:');
system(sprintf('%s -r "echo \'This update code was run over shell\';"', $opts['p']));
