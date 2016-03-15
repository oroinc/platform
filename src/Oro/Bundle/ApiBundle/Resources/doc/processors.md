Processors
==========

Table of Contents
-----------------
 - [Overview](#overview)
 - [Creating a processor](#creating-a-processor)
 - [Processor conditions](#processor-conditions)
 - [Examples of processor conditions](#examples-of-processor-conditions)

Overview
--------

A processor is the main element where a business logic of Data API is implemented. Each processor must implement [ProcessorInterface](../../../../Component/ChainProcessor/ProcessorInterface.php) and be registered in the dependency injection container using the `oro.api.processor` tag.

Please see [actions](./actions.md) section for more details about where and how processors are used.

Also you can use the [oro:api:debug](./debug_commands.md#oroapidebug) command to see all actions and processors.

Creating a processor
--------------------

To create a new processor just create a class implements [ProcessorInterface](../../../../Component/ChainProcessor/ProcessorInterface.php) and [tag](http://symfony.com/doc/current/book/service_container.html#book-service-container-tags) it with the `oro.api.processor` name.

```php
<?php

namespace Acme\Bundle\ProductBundle\Api\Processor;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * A short description what the processor does.
 **/
class DoSomething implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
	    // do some work here
    }
}
```

```yaml
services:
    acme.api.processor.do_something:
        class: Acme\Bundle\ProductBundle\Api\Processor\DoSomething
        tags:
            - { name: oro.api.processor, action: get, group: normalize_input, priority: 10 }
```

Please note that:

- The name of a processor usually starts with a verb and the `Processor` suffix is not used.
- A processor must be a public service because it is loaded on demand.
- The `priority` attribute is used to control the order in which processors are executed. The highest the priority, the earlier a processor is executed. Default value is 0. The possible range is from -255 to 255. But for some types of processors the range can be different. More details you can find in the [documentation of the ChainProcessor](../../../../Component/ChainProcessor/README.md#types-of-processors) component. If several processors have the same priority the order they are executed is unpredictable.

The list of all existing processors you can find in the [Processor](../../Processor) folder.

Processor conditions
--------------------

When you register a processor in the dependency injection container you can specify conditions when the processor should be executed. The attributes of the `oro.api.processor` tag is used to specify conditions. Lets see a very simple condition which is used to filter processors by the action:

```yaml
services:
    acme.api.processor.do_something:
        class: Acme\Bundle\ProductBundle\Api\Processor\DoSomething
        tags:
            - { name: oro.api.processor, action: get }
```

In this case the `acme.api.processor.do_something` will be executed only in scope of the `get` action, for other actions this processor will be skipped.

The main goal of the conditions is to provide a simple way to specify which processors are required to accomplish some work. Also it is very important to understand that the processors are not fit the conditions will not be loaded from the dependency injection container at all. So, using of the conditions allows to create fast Data API.

The types of conditions depend on registered [Applicable Checkers](../../../../Component/ChainProcessor/README.md#applicable-checkers). By default the following checkers are registered:

- [GroupRangeApplicableChecker](../../../../Component/ChainProcessor/GroupRangeApplicableChecker.php)
- [SkipGroupApplicableChecker](../../../../Component/ChainProcessor/SkipGroupApplicableChecker.php)
- [MatchApplicableChecker](../../../../Component/ChainProcessor/MatchApplicableChecker.php)

This allows to build conditions based on any attribute from the context.

Examples of processor conditions
--------------------------------

- No conditions. A processor is executed for all actions.

```yaml
    tags:
        - { name: oro.api.processor }
```

- A processor is executed only for a specified action.

```yaml
    tags:
        - { name: oro.api.processor, action: get_list }
```

- A processor is executed only for a specified action and group.

```yaml
    tags:
        - { name: oro.api.processor, action: get_list, group: initialize }
```

- A processor is executed only for a specified action, group and request type.

```yaml
    tags:
        - { name: oro.api.processor, action: get_list, group: initialize, requestType: json_api }
```

-  A processor is executed for all requests except a specified one.

```yaml
    tags:
        - { name: oro.api.processor, action: get_list, group: initialize, requestType: !json_api }
```

-  A processor is executed only for REST requests conform [JSON.API](http://jsonapi.org/) specification.

```yaml
    tags:
        - { name: oro.api.processor, action: get_list, group: initialize, requestType: rest&json_api }
```

- A processor is executed for several specified actions.

```yaml
    tags:
        - { name: oro.api.processor, action: get, group: initialize, priority: 10 }
        - { name: oro.api.processor, action: get_list, group: initialize, priority: 5 }
```

- A processor is executed only for a specified entity.

```yaml
    tags:
        - { name: oro.api.processor, action: get_list, group: initialize, class: "Oro\Bundle\UserBundle\Entity\User" }
```

More examples you can find in [configuration of existing processors](../config). See `processors.*.yml` files.
