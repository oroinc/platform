# Search Relevance Weight

This article describes the purpose of search relevance weigh and provides two ways to customize this relevance 
in the standard search index.

## What Search Relevance Weight Is

_Search relevance weight_ (also called _search weight_ or _search relevance_) is a positive decimal number that affects the order of search results. It is used as a multiplier for the original relevance calculated by the search engine. 
The original search relevance may differ depending on the search query and additional conditions. The multiplier enables a developer to change the original search relevance which in turn changes the order of the results.

From the code level perspective, search relevance weight is just another search index decimal field called `relevance_weight`. This name is stored in the `Oro\Bundle\SearchBundle\Engine\IndexerInterface::WEIGHT_FIELD` constant that enables a developer to fill this field with the data during the indexation and pass it to the search engine. Then the search engine will start processing the data. 

The default search relevance weight is `1.0`. If no custom value is specified for this multiplier, then the original search engine relevance is used.

## Relevance Weight Customization

There are two main ways how search relevance weight can be passed to the search index:
* via the mapping of a custom decimal field 
* via an event listener. 

### Custom Decimal Field

To use this approach, add a new custom decimal field to the required entity and then map it to the `relevance_weight` field. This way the application automatically fills the field with the appropriate value from this custom field, so a developer should only add certain data to the `relevance_weight` field.

For example, to add a value to the Business Unit entity:

1. Add a custom decimal field ( e.g. `buSearchRelevance` (`precision=8`, `scale=2`)) via the entity management in the UI or via the migration in code. 
2. Map this field to the `relevance_weight` field via the appropriate configuration in the `Resources/config/oro/search.yml` file. The file can be created in any bundle. 

The illustration for this example is below:

```yml
search:
    Oro\Bundle\OrganizationBundle\Entity\BusinessUnit:
        fields:
            -
                name:                   buSearchRelevance
                target_type:            decimal
                target_fields:          [relevance_weight]
```

3. Clear the cache using the `bin/console cache:clear --env=prod` command.
4. Restart all consumers. 

If you have already populated values, you may need to run reindexation using the `bin/console oro:search:reindex --env=prod`
command to pass relevance weight to search index.

### Event Listener

This approach requires creation of an event listener responsible for dynamic calculation of search relevance weight and setting it during the entity reindexation. To create an event listener, use the `oro_search.prepare_entity_map` event and an associated `Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent` event class.

First, create an event listener class:

```php
<?php

namespace Acme\Bundle\TestBundle\EventListener;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Query\Query;

class SetSearchRelevanceWeightListener
{
    public function onPrepareEntityMap(PrepareEntityMapEvent $event)
    {
        $entity = $event->getEntity();

        // set higher search relevance weight for the specific business unit
        if ($entity instanceof BusinessUnit && $entity->getId() === 1) {
            $data = $event->getData();
            $data[Query::TYPE_DECIMAL][IndexerInterface::WEIGHT_FIELD] = 2.5;
            $event->setData($data);
        }
    }
}
```

Then, register this event listener in the DI container:

```yml
services:
    acme_test.event_listener.search.set_search_relevance_weight:
        class: 'Acme\Bundle\TestBundle\EventListener\SetSearchRelevanceWeightListener'
        tags:
            - { name: kernel.event_listener, event: oro_search.prepare_entity_map, method: onPrepareEntityMap }
```

Finally, clear the cache using the `bin/console cache:clear --env=prod` command and trigger reindexation of the required entity using the `bin/console oro:search:reindex 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit' --env=prod` command. 
