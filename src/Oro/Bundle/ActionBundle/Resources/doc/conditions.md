# Conditions

## Table of Contents

 - [Add Custom Condition](#add-custom-condition)
 - [Configurable Condition](#configurable-condition)

## Add Custom Condition

The conditions are based on the [ConfigExpression](/src/Oro/Component/ConfigExpression/README.md) component.

To add custom condition, simply add a service to DIC with the "oro_action.condition" tag, for example:

```
parameters:
    oro_action.condition.blank.class: Oro\Bundle\ActionBundle\ConfigExpression\Blank
services:
    oro_action.condition.blank:
        class: %oro_action.condition.blank.class%
        tags:
            - { name: oro_action.condition, alias: blank|empty }
```

The "|" symbol in alias can be used to demonstrate several aliases. Note that service class must implement Oro\Component\ConfigExpression\ExpressionInterface.

## Configurable Condition

**Alias:** - the option is configurable.

**Description:** - uses Condition Assembler to assemble conditions from passed configuration.
This condition is NOT intended to be used in configuration of Action.
But it can be used to create a condition based on configuration in runtime.

**Options:** - valid configuration of conditions.

**Code Example**

Code Example is a value of the "call_timeout" attribute. It is not blank, and it equals to 20.

```php
$configuration = array(
    '@and' => array(
        '@not_blank' => array('$call_timeout'),
        '@equal' => array('$call_timeout', 20)
    )
);
/** @var $conditionFactory \Oro\Bundle\ActionBundle\Model\Condition\ConditionFactory */
$condition = $conditionFactory->create(Configurable::ALIAS, $configuration);

/** @var object $data */
$data->call_timeout = 20;

var_dump($condition->evaluate($data)); // will output TRUE
```
