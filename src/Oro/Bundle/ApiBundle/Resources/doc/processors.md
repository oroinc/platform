# Processors

 - [Overview](#overview)
 - [Creating a Processor](#creating-a-processor)
 - [Processor Conditions](#processor-conditions)
 - [Examples of Processor Conditions](#examples-of-processor-conditions)
 - [Error Handling](#error-handling)

## Overview

A processor is the main element that implements the business logic of the data API. Each processor must implement [ProcessorInterface](../../../../Component/ChainProcessor/ProcessorInterface.php) and be registered in the dependency injection container using the `oro.api.processor` tag.

Please see [actions](./actions.md) section for more details about where and how processors are used.

Execute the [oro:api:debug](./commands.md#oroapidebug) command to display all actions and processors.

## Creating a processor

To create a new processor, create a class that implements [ProcessorInterface](../../../../Component/ChainProcessor/ProcessorInterface.php) and [tag](http://symfony.com/doc/current/book/service_container.html#book-service-container-tags) it with the `oro.api.processor` name.

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
- The `priority` attribute is used to control the order in which processors are executed. The highest the priority, the earlier a processor is executed. The default value is 0. The possible range is from -255 to 255. However, for some types of processors the range can be different. For more details, see the [documentation of the ChainProcessor](../../../../Component/ChainProcessor/README.md#types-of-processors) component. If several processors have the same priority, the order in which they are executed is unpredictable.
- Each processor should check whether its work is already done because there can be a processor with a higher priority which does the same but in another way. For example, such processors can be created for customization purposes.
- As the data API resources can be created for any type of objects, not only ORM entities, it is always a good idea to check whether a processor is applicable for ORM entities. This check is very fast and allows avoiding possible logic issues and performance impact. Please use the `oro_api.doctrine_helper` service to get an instance of [Oro\Bundle\ApiBundle\Util\DoctrineHelper](../../Util/DoctrineHelper.php) as this class is optimized to be used in the data API stack. An example:

```php
    public function process(ContextInterface $context)
    {
        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        // do some work
    }
```

The list of all existing processors you can find in the [Processor](../../Processor) directory.

## Processor Conditions

When you register a processor in the dependency injection container, you can specify conditions when the processor should be executed. Use the attributes of the `oro.api.processor` tag to specify conditions. Any context property which is scalar, array, or object (instance of the [ToArrayInterface](../../../../Component/ChainProcessor/ToArrayInterface.php)) can be used in the conditions. 

For example, a very simple condition which is used to filter processors by the action:

```yaml
services:
    acme.api.processor.do_something:
        class: Acme\Bundle\ProductBundle\Api\Processor\DoSomething
        tags:
            - { name: oro.api.processor, action: get }
```

In this case, the `acme.api.processor.do_something` is executed only in scope of the `get` action, for other actions this processor is skipped.

Conditions provide a simple way to specify which processors are required to accomplish a work. Pay attention that the dependency injection container does not load processors that does not fit the conditions. Use conditions to create the fast data API.

This allows building conditions based on any attribute from the context.

The types of conditions depend on the registered [Applicable Checkers](../../../../Component/ChainProcessor/README.md#applicable-checkers). By default, the following checkers are registered:

- [MatchApplicableChecker](../../Processor/MatchApplicableChecker.php)

For performance reasons, the functionality of [SkipGroupApplicableChecker](../../../../Component/ChainProcessor/SkipGroupApplicableChecker.php) and [GroupRangeApplicableChecker](../../../../Component/ChainProcessor/GroupRangeApplicableChecker.php) was implemented as part of [OptimizedProcessorIterator](../../Processor/OptimizedProcessorIterator.php).

## Examples of Processor Conditions

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

- A processor is executed only for a specified action, group, and request type.

```yaml
    tags:
        - { name: oro.api.processor, action: get_list, group: initialize, requestType: rest }
```

-  A processor is executed for all requests except a specified one.

```yaml
    tags:
        - { name: oro.api.processor, action: get_list, group: initialize, requestType: !rest }
```

-  A processor is executed only for REST requests that conform to the [JSON.API](http://jsonapi.org/) specification.

```yaml
    tags:
        - { name: oro.api.processor, action: get_list, group: initialize, requestType: rest&json_api }
```

-  A processor is executed either for REST requests or requests that conform to the [JSON.API](http://jsonapi.org/) specification.

```yaml
    tags:
        - { name: oro.api.processor, action: get_list, group: initialize, requestType: rest|json_api }
```

**Please note** that a value can contain either `&` (logical AND) or `|` (logical OR) operators, but it is not possible to combine them.

-  A processor is executed for all REST requests excluding requests that conform to the [JSON.API](http://jsonapi.org/) specification.

```yaml
    tags:
        - { name: oro.api.processor, action: get_list, group: initialize, requestType: rest&!json_api }
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
        - { name: oro.api.processor, action: get_list, group: initialize, class: 'Oro\Bundle\UserBundle\Entity\User' }
```

- A processor is executed only for entities that implement a certain interface or extend a certain base class. Currently, there are two attributes that compared by the **instance of** instead of **equal** operator. These attributes are **class** and **parentClass**.

```yaml
    tags:
        - { name: oro.api.processor, action: get_list, group: initialize, class: 'Oro\Bundle\UserBundle\Entity\AbstractUser' }
```

For more examples, see the [configuration of existing processors](../config). See `processors.*.yml` files.

## Error Handling

There are several types of errors that may occur during the processing of a request:

- **Validation errors** - A validation error occurs if a request has some invalid parameters, headers, or data.
- **Security errors** - This type of error occurs if an access is denied to a requested, updating, or deleting entity.
- **Unexpected errors** - These errors occurs if an unpredictable problem happens. For example, no access to a database or a file system, requested entity does not exist, updating entity is blocked, etc.

Please note that to validate the input data for the [create](./actions.md#create-action) and [update](./actions.md#update-action) actions the best solution is to use validation constraints. In most cases it helps avoid writing any PHP code and configuring the required validation rules in `Resources/config/oro/api.yml`. For the detailed information on how to add custom validation constraints, see the [Forms and Validators Configuration](./forms.md) topic. The following example shows how to add a validation constraint via `Resources/config/oro/api.yml`:

```yaml
api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            fields:
                primaryEmail:
                    form_options:
                        constraints:
                            # add Symfony\Component\Validator\Constraints\Email validation constraint
                            - Email: ~
```

If an error occurs in a processor, the main execution flow is interrupted and the control is passed to a special group of processors named **normalize_result**. This is true for all types of errors except for the errors that occur in processors of the **normalize_result** group. if If any of the processors of this group raises an exception this groups, the execution flow is interrupted. However, these processors can safely add new errors into the [Context](./actions.md#context-class) and the execution of the next processors will not be interrupted. For the implementation details, see [NormalizeResultActionProcessor](../../Processor/NormalizeResultActionProcessor.php).

An error is represented by the [Error](../../Model/Error.php) class. Additionally, the [ErrorSource](../../Model/ErrorSource.php) class can be used to specify a source of an error, e.g. the name of a URI parameter or the path to a property in the  data. These classes have the following methods:

**Error** class

- **create(title, detail)** *static* - Creates an instance of the **Error** class.
- **createValidationError(title, detail)** *static* - Creates an instance of the **Error** class that represents a violation of a validation constraint.
- **createByException(exception)** *static* - Creates an instance of the **Error** class based on a given exception object.
- **getStatusCode()** - Retrieves the HTTP status code applicable to this problem.
- **getCode()** - Retrieves an application-specific error code.
- **setCode(code)** - Sets an application-specific error code.
- **getTitle()** - Retrieves a short, human-readable summary of the problem that should not change from occurrence to occurrence of the problem.
- **setTitle(title)** - Sets a short, human-readable summary of the problem that should not change from occurrence to occurrence of the problem.
- **getDetail()** - Retrieves a human-readable explanation specific to this occurrence of the problem.
- **setDetail(detail)** - Sets a human-readable explanation specific to this occurrence of the problem.
- **getSource()** - Retrieves the instance of [ErrorSource](../../Model/ErrorSource.php) that represents a source of this occurrence of the problem.
- **setSource(source)** - Sets the instance of [ErrorSource](../../Model/ErrorSource.php) that represents a source of this occurrence of the problem.
- **getInnerException()** - Gets Retrieves an exception object that caused this occurrence of the problem.
- **setInnerException(exception)** - Sets an exception object that caused this occurrence of the problem.
- **trans(translator)** - Translates all attributes that are represented by the [Label](../../Model/Label.php) object.

**ErrorSource** class

- **createByPropertyPath(propertyPath)** *static* - Creates an instance of the **ErrorSource** class that represents the path to a property caused the error.
- **createByPointer(pointer)** *static* - Creates an instance of the **ErrorSource** class that represents a pointer to a property in the request document that caused the error.
- **createByParameter(parameter)** *static* - Creates an instance of the **ErrorSource** class that represents the URI query parameter that caused the error.
- **getPropertyPath()** - Retrieves the path to a property that caused the error. For example, "title" or "author.name".
- **setPropertyPath(propertyPath)** - Sets the path to a property that caused the error.
- **getPointer()** - Retrieves a pointer to a property in the request document that caused the error. For JSON , the pointer conforms to the [RFC 6901](https://tools.ietf.org/html/rfc6901). For example, "/data" for a primary data object or "/data/attributes/title" for a specific attribute.
- **setPointer(pointer)** - Sets a pointer to a property in the request document that caused the error.
- **getParameter()** - Retrieves URI query parameter that caused the error.
- **setParameter(parameter)** - Sets URI query parameter that caused the error.

Let us consider how a processor can inform that some error is occurred.

The simplest way is just throw an exception. For example:

```php
<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\QueryBuilder;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Loads entity using the EntitySerializer component.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadEntityByEntitySerializer implements ProcessorInterface
{
    /** @var EntitySerializer */
    protected $entitySerializer;

    /**
     * @param EntitySerializer $entitySerializer
     */
    public function __construct(EntitySerializer $entitySerializer)
    {
        $this->entitySerializer = $entitySerializer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // unsupported query
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // an entity configuration does not exist
            return;
        }

        $result = $this->entitySerializer->serialize($query, $config);
        if (empty($result)) {
            $result = null;
        } elseif (count($result) === 1) {
            $result = reset($result);
        } else {
            throw new RuntimeException('The result must have one or zero items.');
        }

        $context->setResult($result);

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup('normalize_data');
    }
}
```

This way is good to for unexpected and security errors (for security errors just throw `Symfony\Component\Security\Core\Exception\AccessDeniedException`). The raised exception is converted to the **Error** object automatically by [NormalizeResultActionProcessor](../../Processor/NormalizeResultActionProcessor.php). The services named as exception text extractors automatically fill the meaningful properties of the error objects (like HTTP status code, title, and description) based on the underlying exception object. The default implementation of such extractor is [ExceptionTextExtractor](../../Request/ExceptionTextExtractor.php). To add a new extractor, create a class that implements [ExceptionTextExtractorInterface](../../Request/ExceptionTextExtractorInterface.php) and tag it with `oro.api.exception_text_extractor` in the dependency injection container.

Another way to add an **Error** object to the context is good for validation errors because it allows you to add several errors:

```php
<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;

/**
 * Makes sure that the identifier of an entity exists in the context.
 */
class ValidateEntityIdExists implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        $entityId = $context->getId();
        if (empty($entityId)) {
            $context->addError(
                Error::createValidationError(
                    Constraint::ENTITY_ID,
                    'The identifier of an entity must be set in the context.'
                )
            );
        }
    }
}
```

Please note that the default HTTP status code for validation errors is `400 Bad Request`. If needed, another HTTP status code can be set, e.g. by passing it as a third argument of the `Error::createValidationError` method.

The [Constraint](../../Request/Constraint.php) class contains titles for different kind of validation errors. All titles end with word *constraint*. It is recommended to use the same template when adding custom types. 

All data API logs are written into the **api** channel. To inject the data API logger directly to your processors, use the [common way](http://symfony.com/doc/current/reference/dic_tags.html#monolog-logger). For example:

```yaml
    acme.api.some_processor:
        class: Acme\Bundle\AcmeBundle\Api\Processor\DoSomething
        arguments:
            - '@logger'
        tags:
            - { name: oro.api.processor, ... }
            - { name: monolog.logger, channel: api }
```
