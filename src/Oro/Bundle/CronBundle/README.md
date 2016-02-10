OroCronBundle
=============

An interface and scheduler for time-based commands execution.

## Usage ##

All you need is to add `oro:cron` command to a system cron (on *nix systems), for example:

``` bash
*/1 * * * * /usr/local/bin/php /path/to/app/console --env=prod oro:cron >> /dev/null
```

On Windows you can use Task Scheduler from Control Panel.

If you want to make your console command auto-scheduled need to do following:

 - rename your command to start with `oro:cron:`
 - your command should implement `Oro\Bundle\CronBundle\Command\CronCommandInterface`
 
OR

- add new record to entity `Oro\Bundle\CronBundle\Entity\Schedule`
