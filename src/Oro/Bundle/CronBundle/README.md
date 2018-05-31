# OroCronBundle

OroCronBundle introduces a command used in the crontab configuration and in the interface which allows to define the console commands execution schedule.

## Usage

All you need is to add `oro:cron` command to a system cron (on *nix systems), for example:

``` bash
*/1 * * * * /usr/local/bin/php /path/to/bin/console --env=prod oro:cron >> /dev/null
```

On Windows you can use Task Scheduler from Control Panel.

If you want to make your console command auto-scheduled need to do following:

 - rename your command to start with `oro:cron:`
 - your command should implement `Oro\Bundle\CronBundle\Command\CronCommandInterface`
 
OR

- add new record to entity `Oro\Bundle\CronBundle\Entity\Schedule`

## Synchronous CRON commands

By default, all CRON commands are executed asynchronously by sending a message to the queue.

Sometimes it is necessary to execute a CRON command immediately when CRON triggers it, without sending the message to the queue.

To do this, a CRON command should implement interface [SynchronousCommandInterface](./Command/SynchronousCommandInterface.php).

In this case, the command will be executed as a background process.
