## Table of Contents

 - [Idea](#idea)
 - [Postponing in Strategy](#postponing-in-strategy)
 - [Postponing in Strategy Event](#postponing-in-strategy-event)
 
## Idea

When the data from one row in the import file depends on the data in another row (for example, a subsidiary Customer that has another headquarters Customer as a parent), it is critical to process the parent row first and proceed with importing the dependent row afterward. You can analyze the import file and track this kind of dependencies. You can postpone processing the row that precedes the data it depends on by adding the following logics in the Strategy or Strategy Event.

## Postponing in Strategy

Example of usage

```php
<?php

class CustomAddOrReplaceStrategy extends ConfigurableAddOrReplaceStrategy
{
        /**
         * {@inheritdoc}
         */
        protected function beforeProcessEntity($entity)
        {
            $entity = parent::beforeProcessEntity($entity);
            
            if ($this->shouldBePostponed($entity)) {
                $this->context->addPostponedRow($this->context->getValue('rawItemData'));
                
                return null;
            }
    
            return $entity;
        }
        
        // Rest of your code here
        // ...
}
```

## Postponing in Strategy Event

Example of usage

```php
<?php

class CustomerTaxCodeImportExportSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            StrategyEvent::PROCESS_AFTER => ['afterImportStrategy', -255],
        ];
    }
    
    public function afterImportStrategy(StrategyEvent $event)
    {
        $event->getContext()->addPostponedRow(
            $event->getContext()->getValue('rawItemData')
        );
    }
}
```
