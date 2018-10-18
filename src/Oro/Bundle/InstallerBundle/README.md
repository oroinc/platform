# OroInstallerBundle

OroInstallerBundle enables developers to install Oro applications in a prepared environment using the CLI and to define activities for the installation process on a bundle level.

## Forewords
To run the installer on existing setup, you need to update parameters.yml file:
``` yaml
# ...
session_handler: ~
installed: ~
```

## Usage
If you are using distribution package, you will be redirected to installer page automatically.

Otherwise, following installation instructions offered:
``` bash
$ git clone https://github.com/orocrm/crm-application.git
$ cd crm-application
$ wget http://getcomposer.org/composer.phar
$ php composer.phar install
$ php bin/console oro:install
```

## Events
To add additional actions to the installation process you may use event listeners.
Currently, there are three events dispatched:

### `installer.database_preparation.before`
### `installer.database_preparation.after`
Dispatched right before and after all database manipulation (creating table structure, executing migrations, loading demo-data (if set), etc.).
This events can be used to modify database or execute some some service commands to prepare database for usage.
Use next sample code to subscribe on this events:
``` yaml
services:
    installer.listener.database_preparation.after.event:
        class:  Acme\Bundle\MyBundle\EventListener\MyListener
        tags:
            - { name: kernel.event_listener, event: installer.database_preparation.after, method: onAfterDatabasePreparation }
```

``` php
<?php

namespace Acme\Bundle\MyBundle\EventListener;

use Oro\Bundle\InstallerBundle\InstallerEvent

class MyListener
{
    public function onAfterDatabasePreparation(InstallerEvent $event)
    {
        // do something
    }
}
```

### `installer.finish`
Dispatched when the installation is finished.
Example:

``` yaml
services:
    installer.listener.finish.event:
        class:  Acme\Bundle\MyBundle\EventListener\MyListener
        tags:
            - { name: kernel.event_listener, event: installer.finish, method: onFinish }
```

``` php
<?php

namespace Acme\Bundle\MyBundle\EventListener;

class MyListener
{
    public function onFinish()
    {
        // do something
    }
}

```

## Sample data
To provide demo fixtures for your bundle just place them in "YourBundle\Data\Demo" directory.

## Additional install files in bundles and packages

To add additional install scripts during install process you can use install.php files in your bundles and packages.
This install files will be run before last clear cache during installation.

This file must be started with `@OroScript` annotation with script label.

Example:
``` php
<?php
/**
 * @OroScript("Your script label")
 */

 // here you can add additional install logic

```

The following variables are available in installer script:

 - `$container` - Symfony2 DI container
 - `$commandExecutor` - An instance of [CommandExecutor](./CommandExecutor.php) class. You can use it to execute Symfony console commands

All outputs from installer script will be logged in oro_install.log file or will be shown in console in you use console installer.

## Launching plain PHP script in OroPlatform context
In some cases you may need to launch PHP scripts in context of OroPlatform. It means that you need an access to Symfony DI container. Examples of such cases may be some installation or maintenance sctipts. To achieve this you can use `oro:platform:run-script` command.
Each script file must be started with `@OroScript` annotation. For example:
``` php
<?php
/**
 * @OroScript("Your script label")
 */

 // here you can write some PHP code

```

The following variables are available in a script:

 - `$container` - Symfony2 DI container
 - `$commandExecutor` - An instance of [CommandExecutor](./CommandExecutor.php) class. You can use it to execute Symfony console commands

## Notes
If you have multiple PHP versions installed, you need to configure `PHP_PATH` variable with PHP binary path used
by web server

 - Apache2: `SetEnv PHP_PATH /usr/bin/php`

 - Nginx: `fastcgi_param PHP_PATH /usr/bin/php;`

 - PHP Built-in server: `/usr/bin/php bin/console...`
