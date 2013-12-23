<?php

$opts = getopt('p:');
system(sprintf('%s -r "echo \'This uninstall code was run over shell\';"', $opts['p']));