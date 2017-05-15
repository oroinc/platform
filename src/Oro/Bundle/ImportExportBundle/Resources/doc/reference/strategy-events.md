Strategy events
======

Table of Contents
-----------------
 - [Where to find](#where-to-find)
 - [How to use](#how-to-use)
 - [PROCESS_BEFORE](#process-before)
 - [PROCESS_AFTER](#process-after)

Where to find
--------
All strategy events are available in Oro\Bundle\ImportExportBundle\Event\StrategyEvent class.

How to use
----------
```php
<?php

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;

class CustomImportExportSubscriber implements EventSubscriberInterface {
    public static function getSubscribedEvents()
    {
        return [
            StrategyEvent::PROCESS_BEFORE => 'beforeImportStrategy',
            StrategyEvent::PROCESS_AFTER => 'afterImportStrategy',
        ];
    }
    
    public function beforeImportStrategy(StrategyEvent $event)
    {
        //YOUR IMPLEMENTATION
    }

    public function afterImportStrategy(StrategyEvent $event)
    {
        //YOUR IMPLEMENTATION
    }
}
```

PROCESS_BEFORE
--------------
This event occurs just before entity strategy is run.
Can be used to prepare entity before it will be processed by strategy.

PROCESS_AFTER
-------------
This event occurs after entity strategy finishes it job.
It can be used to provide additional validation of the entity.
