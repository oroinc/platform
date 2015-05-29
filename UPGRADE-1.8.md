UPGRADE FROM 1.7 to 1.8
=======================

####PropertyAccess Component
- Removed `Oro\Component\PropertyAccess\PropertyPath` and `Oro\Component\PropertyAccess\PropertyPathInterface`, `Symfony\Component\PropertyAccess\PropertyPath` and `Symfony\Component\PropertyAccess\PropertyPathInterface` should be used instead
- Removed `Oro\Component\PropertyAccess\Exception` namespace, `Symfony\Component\PropertyAccess\Exception` is used

####ImportExportBundle
 - `Oro\Bundle\ImportExportBundle\Context\ContextInterface` added $incrementBy integer parameter for methods: incrementReadCount, incrementAddCount, incrementUpdateCount, incrementReplaceCount, incrementDeleteCount, incrementErrorEntriesCount

####WorkflowBundle
 Migrate conditions logic to ConfigExpression component:
 - Removed `Oro\Bundle\WorkflowBundle\Model\Condition\ConditionInterface`, `Oro\Component\ConfigExpression\ExpressionInterface` should be used instead
 - Removed `Oro\Bundle\WorkflowBundle\Model\Condition\ConditionFactory`, `Oro\Component\ConfigExpression\ExpressionFactory` should be used instead
 - Removed `Oro\Bundle\WorkflowBundle\Model\Condition\ConditionAssembler`, `Oro\Component\ConfigExpression\ExpressionAssembler` should be used instead
 - Removed all conditions in `Oro\Bundle\WorkflowBundle\Model\Condition` namespace, corresponding conditions from ConfigExpression component (`Oro\Component\ConfigExpression\Condition` namespace) should be used instead

####FormBundle
 - `Oro\Bundle\FormBundle\Form\Extension\RandomIdExtension` by default adds unique suffix to id attribute of each form type

####SyncBundle
Removed parameters `websocket_host` and `websocket_port` from `parameters.yml`. Instead the following websocket configuration is used:
``` yaml
    websocket_bind_address:  0.0.0.0
    websocket_bind_port:     8080
    websocket_frontend_host: "*"
    websocket_frontend_port: 8080
    websocket_backend_host:  "*"
    websocket_backend_port:  8080
```
- `websocket_bind_port` and `websocket_bind_address` specify port and address to which the Clank server binds on startup and waits for incoming requests. By default (0.0.0.0), it listens to all addresses on the machine
- `websocket_backend_port` and `websocket_backend_host` specify port and address to which the application should connect (PHP). By default ("*"), it connects to 127.0.0.1 address.
- `websocket_frontend_port` and `websocket_frontend_host` specify port and address to which the browser should connect (JS). By default ("*"), it connects to host specified in the browser.

####SoapBundle
- Removed `EntitySerializerManagerInterface`. The serialization methods in `ApiEntityManager` class should be used instead.
