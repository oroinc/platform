Oro Log Component
====================

`Oro Log Component` provides additional output logger for use in cli commands.

OutputLogger
--------------

 - implements Psr\Log\AbstractLogger
 - logs to Symfony\Component\Console\Output\OutputInterface
 - have $alwaysLogErrors constructor argument - that allow to log all errors even in quiet mode
 - takes verbosity level as constructor argument or from OutputInterface object instance, if argument not specified
