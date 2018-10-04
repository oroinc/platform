# Events

## Table of contents

- [Events](#events-list)
    - [Build events](#build-events)
    - [Result events](#result-events)


## Events list

Datagrids in Oro applications are highly customizable. It is possible to modify an existing grid in order to fetch more data than it was originally defined in the grid configuration.
In order to provide extendability points, `build` and `result` events have been introduced.

## Build events

Build events are dispatched by the `Builder` class right before and immediately after processing configuration and building datasource.
They are useful in case you need to modify datagrid or a query configuration.
Four events are being dispatched during build process:

* Class `BuildBefore`, event name: `oro_datagrid.datagrid.build.before`
* Class `BuildBefore`, event name: `oro_datagrid.datagrid.build.before.DATAGRID_NAME`
* Class `BuildAfter`, event name: `oro_datagrid.datagrid.build.after`
* Class `BuildAfter`, event name: `oro_datagrid.datagrid.build.after.DATAGRID_NAME`

### BuildBefore events

By listening to these events you can add new elements to the grid configuration or modify the already existing configuration in your event listener.
You can use the generic `build.before` event for listening to all or specific datagrids, which will be called 
only for a given datagrid - `build.before.DATAGRID_NAME`.

The `BuildBefore` event class has access to [DatagridConfiguration](../../../Datagrid/Common/DatagridConfiguration.php) and 
[Datagrid](../../../Datagrid/Datagrid.php) instance.

_Please note that at this point datasource has not been initialized yet, therefore calling `$event->getDatagrid()->getDatasource()` returns `null`._

As an illustration, let's add one more column to a specific datagrid. For this, create an event listener and modify the
existing configuration the following way:

```php
<?php

namespace Acme\Bundle\AcmeBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class AdditionalColumnDatagridListener
{
    /**
    * @param BuildBefore $event
    */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $config->offsetSetByPath('[columns][myCustomColumn]', ['label' => 'acme.my_custom_column.label']);
        $config->offsetAddToArrayByPath('[source][query][select]', ['123 as myCustomColumn']);
    }
}
```

Once the listener is created, register it in `services.yml`:

```yaml
acme_bundle.event_listener.datagrid.additional_column:
    class: Acme\Bundle\AcmeBundle\EventListener\Datagrid\AdditionalColumnDatagridListener
    tags:
        - { name: kernel.event_listener, event: oro_datagrid.datagrid.build.before.DATAGRID_NAME, method: onBuildBefore }
```

**Use cases**

* Add additional columns and update query configuration for the translation datagrid: `Oro\Bundle\TranslationBundle\EventListener\Datagrid\LanguageListener`
* Remove `public` column from the system calendar datagrid: `Oro\Bundle\CalendarBundle\EventListener\Datagrid\SystemCalendarGridListener`
* (OroCommerce) Bind user's currency parameter to the checkout grid: `Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener`

### BuildAfter events

By listening to these events you can modify datasource or even the whole datagrid instance. However, the most common case for 
this event is to modify the query (add additional joins, selects, the `where` conditions, etc.).

You can use generic `build.after` event for listening to all or specific datagrids, which will be called 
only for a given datagrid - `build.after.DATAGRID_NAME`.

The `BuildAfter` event class has access to [Datagrid](../../../Datagrid/Datagrid.php) instance.

As an example, let us filter the datagrid by a certain value from the request params. For this, create an event listener and modify the query builder as illustrated below:

```php
<?php

namespace Acme\Bundle\AcmeBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

class FilterByRequestParamListener
{
    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datasource = $event->getDatagrid()->getDatasource();
        if (!$datasource instanceof OrmDatasource) {
            return;
        }
    
        $customFilter = $this->requestStack->getCurrentRequest()->get('custom_filter');
        
        $queryBuilder = $datasource->getQueryBuilder();
        $queryBuilder->andWhere($queryBuilder->expr()->eq('some_column', ':custom_filter'));
        $queryBuilder->setParameter('custom_filter', $customFilter);
    }
}
```

_Please note that this example works only for ORM datasources_

Once the listener is created, register it in `services.yml`:

```yaml
acme_bundle.event_listener.datagrid.filter_by_request_param:
    class: Acme\Bundle\AcmeBundle\EventListener\Datagrid\FilterByRequestParamListener
    tags:
        - { name: kernel.event_listener, event: oro_datagrid.datagrid.build.after.DATAGRID_NAME, method: onBuildAfter }
```

**Use cases**

* Apply additional filtering to the activity email grid: `Oro\Bundle\EmailBundle\EventListener\Datagrid\ActivityGridListener`
* (OroCommerce) Add additional properties to the storefront product grid: `Oro\Bundle\CatalogBundle\EventListener\SearchCategoryFilteringEventListener`

## Result events

Result events are type-specific which means that `datasource` is responsible for dispatching them.
Listening to these events is useful both when you need to access a query (e.g. ORM, search) or modify the results.

As an example, have a look at the [OrmDatasource](../../../Datasource/Orm/OrmDatasource.php). In the `getResult()` 
method it dispatches 4 main and 2 additional events:
* Additional - Class `OrmResultBeforeQuery`, event name: `oro_datagrid.orm_datasource.result.before_query`
* Additional - Class `OrmResultBeforeQuery`, event name: `oro_datagrid.orm_datasource.result.before_query.DATAGRID_NAME`
* Main - Class `OrmResultBefore`, event name: `oro_datagrid.orm_datasource.result.before`
* Main - Class `OrmResultBefore`, event name: `oro_datagrid.orm_datasource.result.before.DATAGRID_NAME`
* Main - Class `OrmResultAfter`, event name: `oro_datagrid.orm_datasource.result.after`
* Main - Class `OrmResultAfter`, event name: `oro_datagrid.orm_datasource.result.after.DATAGRID_NAME`

The first four events are mostly used to access a query at different stages, while the last two are used to modify the results.

Remember to dispatch result events when creating your own [custom datasource type](datasources.md#custom-types).

### ResultBefore events

The purpose of these events is to have the ability to access datagrid or a query instance before datasource starts building the results.
You can use generic `result.before` event for listening to all or specific datagrids, which will be called 
only for a given datagrid - `result.before.DATAGRID_NAME`.

**Use cases**
* Apply ACL to a datagrid datasource: `Oro\Bundle\DataGridBundle\EventListener\OrmDatasourceAclListener`

### ResultAfter events

The purpose of these events is to have ability to modify data after the rows were fetched from the `datasource`.
You can use generic `result.after` event for listening to all or specific datagrids, which will be called 
only for a given datagrid - `result.after.DATAGRID_NAME`.

For instance, if you have complex data that is hard to process with the standard datagrid configuration using YML files,
you can create an event listener and fetch the data once the rows are fetched from the `datasource`.

```php
<?php

namespace Acme\Bundle\AcmeBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

class ComplexDataDatagridListener
{
    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
    
        $complexData = $this->complexService->getComplexDataForRecords($records);
    
        foreach ($records as $record) {
            $recordId = $record->getValue('id');
            $record->addData(['complexData' => $complexData[$recordId]]);
        }
    }
}
```

Once the event listener is created, register it in `services.yml`:

```yaml
acme_bundle.event_listener.datagrid.complex_data:
    class: Acme\Bundle\AcmeBundle\EventListener\Datagrid\ComplexDataDatagridListener
    tags:
        - { name: kernel.event_listener, event: oro_datagrid.orm_datasource.result.after.DATAGRID_NAME, method: onResultAfter }
```

**Use cases**

* Translate workflow fields in the email notification grid: `Oro\Bundle\WorkflowBundle\Datagrid\EmailNotificationDatagridListener`
* (OroCommerce) Add payment methods to the order grid: `Oro\Bundle\OrderBundle\EventListener\OrderDatagridListener`
