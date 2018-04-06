# Oro Log Component

`Oro Log Component` provides additional logger features to Psr\Log component

## OutputLogger

 - implements Psr\Log\AbstractLogger
 - logs to Symfony\Component\Console\Output\OutputInterface
 - can be used with cli output
 - have $alwaysLogErrors constructor argument - that allow to log all errors even in quiet mode
 - takes verbosity level as constructor argument or from OutputInterface object instance, if argument not specified
