# Strategy events

## Table of Contents

 - [Where to Find](#where-to-find)
 - [How to Use](#how-to-use)
 - [PROCESS_BEFORE](#process-before)
 - [PROCESS_AFTER](#process-after)

# Where to Find

All strategy events are available in the Oro\Bundle\ImportExportBundle\Event\StrategyEvent class.

# How to Use

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

# PROCESS_BEFORE

This event occurs just before the entity strategy is run.
It is used to prepare the entity before it is processed by the strategy.

# PROCESS_AFTER

This event occurs after the job of the entity strategy is finished.
It is used to provide additional validation of the entity.
