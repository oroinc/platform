# OroMaintenanceBundle

OroMaintenanceBundle allows you to place your application in maintenance mode by calling two commands in your console. A page with status code 503 appears to users,
it is possible to authorize certain ips addresses stored in your configuration.

## Table of Contents
- [Use](#use)
- [Commands](#commands)
- [Custom error page 503](#custom-error-page-503)
- [Using with a Load Balancer](#using-with-a-load-balancer)
- [Services](#services)

## Use
you have several options for each driver.

Here the complete configuration with the `example` of each pair of class / options.

    #app/config.yml
    oro_maintenance:
        authorized:
            path: /path                                                         # Optional. Authorized path, accepts regexs
            host: your-domain.com                                               # Optional. Authorized domain, accepts regexs
            ips: ['127.0.0.1', '172.123.10.14']                                 # Optional. Authorized ip addresses
            query: { foo: bar }                                                 # Optional. Authorized request query parameter (GET/POST)
            cookie: { bar: baz }                                                # Optional. Authorized cookie
            route:                                                              # Optional. Authorized route name
            attributes:                                                         # Optional. Authorized route attributes
        driver:
             # File driver
            options: {file_path: %kernel.root_dir%/../var/cache/lock}                  # file_path is the complete path for create the file

        #Optional. response code and status of the maintenance page
        response:
            code: 503                                                                  # Http response code of Exception page
            status: "Service Temporarily Unavailable"                                  # Exception page title
            exception_message: "Service Temporarily Unavailable"                       # Message when Exception is thrown 


## Commands

There are two commands:

    oro:maintenance:lock

This command will enable the maintenance according to your configuration.

    oro:maintenance:unlock

This command will disable the maintenance.

You can execute the lock without a warning message which you need to interact with:

    oro:maintenance:lock --no-interaction

## Custom error page 503

In the listener, an exception is thrown when website is under maintenance. This exception is a 'This exception is a 'HttpException' (status 503), to custom your error page
you need to create a error503.html.twig (if you use twig) in:
templates/bundles/TwigBundle/Exception

## Using with a Load Balancer
Some load balancers will monitor the status code  of the http response to stop forwarding traffic  to your nodes. 
If you are using a load balancer you may want to change the status code of the maintenance page to 200, so your users will still see
something. You may change the response code of the status page from 503 by changing the **response.code** configuration.

## Services

You can use the ``oro_maintenance.driver.factory`` service anyway in your app and call ``lock`` and ``unlock`` methods.
For example, you can build a backend module to activate maintenance mode.
In your controller:

    $driver = $this->get('oro_maintenance.driver.factory')->getDriver();
    if ($action === 'lock') {
        $isLocked = $driver->lock();
        $message = $isLocked ? 'Maintenance mode on' : 'Failed to turn on maintenance mode';
    } else {
        $isUnlocked = $driver->unlock();
        $message = $isUnlocked ? 'Maintenance mode off' : 'Failed to turn off maintenance mode';
    }

    $this->get('request_stack')->getSession()->setFlash('maintenance', $message);

    return new RedirectResponse($this->generateUrl('_demo'));


**Warning**: Make sure you have allowed IP addresses if you run maintenance from the backend, otherwise you will find yourself blocked on page 503.

To use maintenance mode functionality bundle also provides `oro_maintenance.maintenance` service.

``` php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;

class AcmeController extends AbstractController
{
    public function indexAction()
    {
        // check if maintenance mode is on
        if ($this->get('oro_maintenance.maintenance')->isOn()) {
            // ...
        }

        // ...
    }

    /**
     * @Route("/maintenance/{mode}", name="acme_maintenance", requirements={"mode"="on|off"})
     */
    public function maintenanceAction($mode = 'on')
    {
        // switch maintenance mode on/off
        if ('on' == $mode) {
            $this->get('oro_maintenance.maintenance')->on();
        } else {
            $this->get('oro_maintenance.maintenance')->off();
        }

        // ...
    }
}
```

In maintenance mode all cron jobs disabled for execution.

**Note:** The driver checks if the maintenance mode has expired, but does not disable it automatically.

**Note**: Ensure that you have read bundle documentation to understand how the installed OroHealthCheckBundle affects
the default behavior of the maintenance mode.
