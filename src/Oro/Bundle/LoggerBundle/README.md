# OroLoggerBundle

OroLoggerBundle extends the [MonologBundle](https://github.com/symfony/monolog-bundle) functionality and provides:
* [error logs email notifications](#error-logs-email-notifications)
* [ability to temporarily decrease log level](#temporarily-decrease-log-level)
* [console commands logging](#logging-console-commands)

For more details on how to use [MonologBundle](https://github.com/symfony/monolog-bundle), refer to the Symfony [documentation](http://symfony.com/doc/current/logging.html).

## Error Logs Email Notifications
To enable error logs email notification run console command `oro:logger:email-notification` with semicolons separated 
recipients, for example:  

    php bin/console oro:logger:email-notification --recipients="admin@example.com;support@example.com"

To disable the notifications run command with `--disable` flag.
  
Or you can configure recipients list using web interface from `System > Configuration > General Setup > Appication Settings > Error Logs 
notifications` section.

To change log level for email notifications update `monolog.handlers.swift.level` parameter at `config_prod.yml`. 

## Temporarily Decrease Log Level
Default log level at production environment is specified by `oro_logger.detailed_logs_default_level` container parameter 
and equals to `error`, you can update it at application configuration.

To find problems you allowed to change this value for a specific time for a specific user, to do this 
run command:  

    php bin/console oro:logger:level debug "1 hour" --user=admin@example.com

Where `debug` is log level and `1 hour` is time interval when the level will be used instead of default, 
`--user` option contains email of user whose log will be affected.

Also you can decrease log level system wide by skipping `--user` option.

## Logging Console Commands

All console commands logged automatically on **ConsoleEvents::COMMAND** and **ConsoleEvents::EXCEPTION**, see [ConsoleCommandSubscriber](./EventSubscriber/ConsoleCommandSubscriber.php).

