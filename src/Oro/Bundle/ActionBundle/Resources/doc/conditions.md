Conditions
==========

Table of Contents
-----------------
 - [Add Custom Condition](#add-custom-condition)
 - [Configurable Condition](#configurable-condition)

Add Custom Condition
-------------------------
The conditions are based on the [ConfigExpression](/src/Oro/Component/ConfigExpression/README.md) component.

To add custom condition simply add a service to DIC with the tag "oro_action.condition", for example:

```
parameters:
    oro_action.condition.blank.class: Oro\Bundle\ActionBundle\ConfigExpression\Blank
services:
    oro_action.condition.blank:
        class: %oro_action.condition.blank.class%
        tags:
            - { name: oro_action.condition, alias: blank|empty }
```

Symbol "|" in alias can be used to have several aliases. Note that service class must implement
Oro\Component\ConfigExpression\ExpressionInterface.

Configurable Condition
----------------------

**Alias:** configurable

**Description:** Uses Condition Assembler to assemble conditions from passed configuration.
This condition is NOT intended to be used in configuration of Action.
But it can be used to create condition based on configuration in runtime.

**Options:**
 - Valid configuration of conditions.

**Code Example**

Is value of attribute "call_timeout" not blank AND equal to 20.
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
