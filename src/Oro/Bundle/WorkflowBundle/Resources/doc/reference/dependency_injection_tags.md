The Dependency Injection Tags
=============================

| Type name | Usage |
|-----------|-------|
| [oro_workflow.changes.listener](#oro_workflowchangeslistener) | Registers listeners to listen for the workflow changes |
| [oro_workflow.changes.subscriber](#oro_workflowchangessubscriber) | Registers subscribers to listen for the workflow changes |
| [oro.workflow.configuration.handler](#oroworkflowconfigurationhandler) | Registers services that handle the workflow configuration updates when it is updated through the API |
| [oro.workflow.definition_builder.extension](#oroworkflowdefinition_builderextension) | Registers services that handle the workflow configuration |
| [oro_workflow.listener.event_trigger_collector.extension](#oro_workflowlistenerevent_trigger_collectorextension) | Registers services that listen for the Trigger events |
| [oro_workflow.processor](#oro_workflowprocessor) | Registers processors for the transition form data preparation and handling |

oro_workflow.changes.listener
-----------------------------
Listener that listens for the [following](../../../Event/WorkflowEvents.php) workflow events.

oro_workflow.changes.subscriber
-------------------------------
Subscriber that listens for the [following](../../../Event/WorkflowEvents.php) workflow events.

oro.workflow.configuration.handler
----------------------------------
For API only. Service that dynamically updates raw workflow configuration before it is built. Must implement [ConfigurationHandlerInterface](../../../Configuration/Handler/ConfigurationHandlerInterface.php).

oro.workflow.definition_builder.extension
-----------------------------------------
Service that dynamically updates raw workflow configuration before it is built. Must implement [WorkflowDefinitionBuilderExtensionInterface](../../../Configuration/WorkflowDefinitionBuilderExtensionInterface.php).

oro_workflow.listener.event_trigger_collector.extension
-------------------------------------------------------
Service that listens for the Trigger events. Must implement [EventTriggerExtensionInterface](../../../EventListener/Extension/EventTriggerExtensionInterface.php).
Also see [Workflow Transition Triggers](./workflow/configuration-reference.md#transition-triggers-configuration) and [Processes Triggers](./processes/index.md#processes-documentation) documentation.

oro_workflow.processor
----------------------
Processor for the transition form data preparation and handling. Must implement [ProcessorInterface](../../../../../Component/ChainProcessor/ProcessorInterface.php).
Available groups: `initialize`, `configure`, `createForm`, `handle`, `normalize` for `non-transit` action and `result` for `transit` action.
