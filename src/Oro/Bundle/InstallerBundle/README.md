OroInstallerBundle
==================

Web installer for OroCRM. Inspired by [Sylius](https://github.com/Sylius/SyliusInstallerBundle).

To run the installer on existing setup, you need to update parameters.yml file:
``` yaml
# ...
session_handler: ~
installed: ~
```

## Usage ##
If you are using distribution package, you will be redirected to installer page automatically.

Otherwise, following installation instructions offered:
``` bash
$ git clone https://github.com/orocrm/crm-application.git
$ cd crm-application
$ wget http://getcomposer.org/composer.phar
$ php composer.phar install
$ php app/console oro:install
```

## Events ##
To add additional actions to the installation process you may use event listeners.
Currently only "onFinish" installer event dispatched.

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

## Sample data ##
To provide demo fixtures for your bundle just place them in "YourBundle\DataFixtures\Demo" directory.

## Additional install files in bundles and packages ##

To add additional install scripts during install process you can use install.php files in your bundles and packages.
This install files will be run before last clear cache during installation.

This file must be started with `@OroScript` annotation with installer label witch will be shown during web install process.

Example:

``` php
<?php
/**
 * @OroScript("Your install label")
 */

 // here you can add additional install logic.

```

The following variables are available in installer script:

 - `$container` - Symfony2 DI container
 - `$commandExecutor` - An instance of [CommandExecutor](./CommandExecutor.php) class. You can use it to execute Symfony console commands

All outputs from installer script will be logged in oro_install.log file or will be shown in console in you use console installer.
