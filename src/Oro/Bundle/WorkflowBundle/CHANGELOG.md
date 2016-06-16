CHANGELOG for BAP-10805
=======================
* Removed class `Oro\Bundle\WorkflowBundle\Field\FieldProvider`
* Removed class `Oro\Bundle\WorkflowBundle\Field\FieldGenerator`
* Updated class constructor `Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder`, removed second argument `$fieldGenerator`
* Updated REST callback `oro_api_workflow_entity_get`, now it uses `oro_entity.entity_provider` service to collect entities and fields
* Removed following services:
    * oro_workflow.field_generator
    * oro_workflow.exclusion_provider
    * oro_workflow.entity_provider
    * oro_workflow.entity_field_provider
    * oro_workflow.entity_field_list_provider
* Removed `Oro\Bundle\WorkflowBundle\Field\FieldGenerator` dependency from class `Oro\Bundle\WorkflowBundle\Model\EntityConnector`
* Removed `Oro\Bundle\WorkflowBundle\Field\FieldGenerator` dependency from class ``\Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener``, for now all required constants moved to this class and should be updated or removed on https://magecore.atlassian.net/browse/BAP-10803