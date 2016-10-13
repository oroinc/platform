OroLoggerBundle
===============

This bundle provide ability to log system events.

We use [MonologBundle](https://github.com/symfony/monolog-bundle) for logging events that implements the [PSR-3 LoggerInterface](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md).

Please see Symfony [documentation](http://symfony.com/doc/current/logging.html) for more details how to use [MonologBundle](https://github.com/symfony/monolog-bundle).

## Logging Console Commands

All console commands logged automatically on **ConsoleEvents::COMMAND** and **ConsoleEvents::EXCEPTION**, see [ConsoleCommandSubscriber](./EventSubscriber/ConsoleCommandSubscriber.php).
