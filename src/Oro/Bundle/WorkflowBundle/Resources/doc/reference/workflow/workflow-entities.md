Workflow Entities
=================

Table of Contents
-----------------
 - [Main Entities](#main-entities)
   - [Workflow](#workflow)
   - [Workflow Registry](#workflow-registry)
   - [Step](#step)
   - [Transition](#transition)
   - [Attribute](#attribute)
   - [Action](#action)
   - [Action Factory](#action-factory)
 - [Entity Assemblers](#entity-assemblers)
   - [Workflow Assembler](#workflow-assembler)
   - [Step Assembler](#step-assembler)
   - [Transition Assembler](#transition-assembler)
   - [Attribute Assembler](#attribute-assembler)
   - [Action Assembler](#action-assembler)
   - [Form Options Assembler](#form-option-assembler)
 - [Database Entities](#database-entities)
   - [Workflow Definition](#workflow-definition)
   - [Workflow Definition Repository](#workflow-definition-repository)
   - [Workflow Item](#workflow-item)
   - [Workflow Item Repository](#workflow-item-repository)
   - [Workflow Step](#workflow-step)
   - [Workflow Transition Record](#workflow-transition-record)
   - [Workflow Entity Acl](#workflow-entity-acl)
   - [Workflow Entity Acl Identity](#workflow-entity-acl-identity)
   - [Workflow Entity Acl Identity Repository](#workflow-entity-acl-identity-repository)
 - [Support Entities](#support-entities)
   - [Workflow Manager](#workflow-manager)
   - [Workflow Data](#workflow-data)
   - [Workflow Result](#workflow-result)
   - [Step Manager](#step-manager)
   - [Transition Manager](#transition-manager)
   - [Attribute Manager](#attribute-manager)
   - [Context Accessor](#context-accessor)
   - [Entity Connector](#entity-connector)
   - [ACL Manager](#acl-manager)
   - [Workflow Entity Voter](#workflow-entity-voter)
   - [Workflow Configuration](#workflow-configuration)
   - [Workflow List Configuration](#workflow-list-configuration)
   - [Workflow Configuration Provider](#configuration-provider)
   - [Workflow Definition Configuration Builder](#workflow-definition-configuration-builder)
   - [Workflow Data Serializer](#workflow-data-serializer)
   - [Workflow Data Normalizer](#workflow-data-normalizer)
   - [Attribute Normalizer](#attribute-normalizer)
   - [Parameter Pass](#parameter-pass)

Main Entities
=============
Workflow
--------
**Class:**
Oro\Bundle\WorkflowBundle\Model\Workflow

**Description:**
Encapsulates all logic of workflow, contains lists of steps, attributes and transitions. Uses Entity Connector for the
connects related entities with workflow entities. Create instance of Workflow Item, performs transition if it's allowed,
gets allowed transitions and start transitions. Delegates operations with aggregated domain models to corresponding
managers, such as Step Manager, Transition Manager and Attribute Manager.

**Methods:**
* **getStepManager()** - get instance of embedded Step Manager;
* **getAttributeManager()** - get instance of embedded Attribute Manager;
* **getTransitionManager()** - get instance of embedded Transition Manager;
* **start(Entity, data, startTransitionName)** - returns new instance of Workflow Item and processes it's start
transition;
* **isTransitionAllowed(WorkflowItem, Transition, errors, fireException)** - calculates whether transition is allowed
for specified WorkflowItem and optionally returns list of errors or/and fire exception;
* **transit(WorkflowItem, Transition)** - performs transit for specified WorkflowItem by name of transition or
transition instance;
* **resetWorkflowData()** - perform reset workflow item data for the specific workflow;
* **createWorkflowItem(Entity, array data)** - create WorkflowItem instance for the specific entity and initialize it
with passed data;
* **getAttributesMapping()** - Get attribute names mapped to property paths if any have;
* **isStartTransitionAvailable(Transition, Entity, array data, errors)** - check that start transition is available
for showing for specified Entity and optionally returns list of errors;
* **isTransitionAvailable(WorkflowItem, Transition, errors)** - check that transitions available for showing
for specified WorkflowItem and optionally returns list of errors;
* **getTransitionsByWorkflowItem(WorkflowItem)** - returns a list of allowed transitions for passed WorkflowItem;
* **getPassedStepsByWorkflowItem(WorkflowItem)** - returns a list of passed latest steps in ascending order from step
with minimum order to step with maximum order;

Workflow Registry
-----------------
**Class:**
Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry

**Description:** Assembles Workflow object using WorkflowAssembler and ConfigProvider then returns Workflow
objects by their names or managed entities.

**Methods:**
* **getWorkflow(workflowName)** - extracts Workflow object by it's name;
* **getActiveWorkflowsByEntityClass(entityOrClass)** - returns all Workflows for passed entity or entity class;
* **hasActiveWorkflowsByEntityClass(entityOrClass)** - checks if entity or entity class have linked workflows.

Step
----
**Class:**
Oro\Bundle\WorkflowBundle\Model\Step

**Description:**
Encapsulated step parameters, contains lists of attributes and allowed transition names, has step template,
isFinal flag, form type and form options. Also has possibility manage ACL permission for the entity actions.

**Methods:**
* **isAllowedTransition(transitionName)** - calculates whether transition with name transitionName allowed for current
step;
* **allowTransition(transitionName)** - allow transition with name transitionName;
* **hasAllowedTransitions()** - check is current step has allowed transitions;
* **disallowTransition(transitionName)** - disallow transition with name transitionName;
* **setEntityAcls(array entityAcls)** - sets ACL permission for the entity actions;
* **isEntityAclDefined(attributeName)** - check is current step has defined ACL permission rules;
* **isEntityUpdateAllowed(attributeName)** - check is current step has ACL permission for update entity;
* **isEntityDeleteAllowed(attributeName)** - check is current step has ACL permission for delete entity;

Transition
----------
**Class:**
Oro\Bundle\WorkflowBundle\Model\Transition

**Description:**
Encapsulates transition parameters, contains init action, condition, pre condition and post action, has next step
property.

**Methods:**
* **isConditionAllowed(WorkflowItem, errors)** - check whether conditions allowed and optionally returns list of errors;
* **isPreConditionAllowed(WorkflowItem, errors)** - check whether preconditions allowed and optionally returns
list of errors;
* **isAllowed(WorkflowItem, errors)** - calculates whether this transition allowed for WorkflowItem
and optionally returns list of errors;
* **isAvailable(WorkflowItem, errors)** - check whether this transition should be shown and optionally returns
list of errors;
* **transit(WorkflowItem)** - performs transition for WorkflowItem;
* **setStart()** - mark transition as start transition;
* **isStart()** - check is current transition start;
* **hasForm()** - if transition has form or not;
* **isHidden()** - check is current transition can be displayed;
* **isUnavailableHidden()** - check is current transition can be hidden;

Attribute
---------
**Class:**
Oro\Bundle\ActionBundle\Model\Attribute

**Description:**
Encapsulates attribute parameters, has label, type and options. Also has possibility manage ACL permission
for the entity actions.

**Methods:**
* **setEntityAcls(array entityAcls)** - sets ACL permission for the entity;
* **isEntityUpdateAllowed(attributeName)** - check is attribute has ACL permission for update entity;
* **isEntityDeleteAllowed(attributeName)** - check is attribute has ACL permission for delete entity;

Action
------
**Interface:**
Oro\Component\Action\Action\ActionInterface

**Description:**
Basic interface for Transition Actions.

**Methods:**
* **execute(context)** - execute specific action for current context (usually context is WorkflowItem instance);
* **initialize(options)** - initialize specific action based on input options;
* **setCondition(condition)** - set optional condition for action;

Action Factory
--------------
**Class:**
Oro\Component\Action\Action\ActionFactory

**Description:**
Creates instances of Transition Actions based on type (alias) and options.

**Methods:**
* **create(type, options, condition)** - creates specific instance of Transition Action. Also has possibility to set
optional condition;

Entity Assemblers
=================

Workflow Assembler
------------------
**Class:**
Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler

**Description:**
Creates instances of Workflow objects based on Workflow Definitions. Requires configuration object to parse
configuration and attribute, step and transition assemblers to assemble appropriate parts of configuration.

**Methods:**
* **assemble(WorkflowDefinition, needValidation)** - assemble and returns instance of Workflow based on input
WorkflowDefinition and optionally can escape Workflow validation;

Step Assembler
--------------
**Class:**
Oro\Bundle\WorkflowBundle\Model\StepAssembler

**Description:**
Creates instances of Steps based on input configuration and Attributes.

**Methods:**
* **assemble(configuration, attributes)** - assemble and returns list of Step instances;

Transition Assembler
--------------------
**Class:**
Oro\Bundle\WorkflowBundle\Model\TransitionAssembler

**Description:**
Creates instances of Transitions based on transition configuration, transition definition configuration, form options
and list of Step entities. Uses ConfigExpression Factory and Action Factory to create configurable conditions and actions.

**Methods:**
* **assemble(array configuration, array definitionsConfiguration, steps, attributes)** - assemble and returns list of Transitions;

Attribute Assembler
-------------------
**Class:**
Oro\Bundle\WorkflowBundle\Model\AttributeAssembler

**Description:**
Assemble Attribute instances based on WorkflowDefinition and source configuration.

**Methods:**
* **assemble(definition, array $configuration)** - assemble and returns list of Attributes;

Action Assembler
----------------
**Class:**
Oro\Component\Action\Action\ActionAssembler

**Description:**
Walks through Action configuration and creates instance of appropriate Actions using Action Factory and ConfigExpression Factory.

**Methods:**
* **assemble(array configuration)** - assemble configuration and returns instance of treeExecutor Actions;

Form Options Assembler
----------------------
**Class:**
Oro\Bundle\WorkflowBundle\Model\FormOptionAssembler

**Description:**
Assembles form options that can be passed to transition and step. Also creating initialization action, if that action exist,
using Action Factory.

**Methods:**
* **assemble(options, attributes, owner, ownerName)** - validate form options, set attributes and assemble with
configuration then returns list of form options;


Database Entities
=================

Workflow Definition
-------------------

**Class:**
Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition

**Description:**
Encapsulates Workflow parameters and serialized array with configuration. Has references on related entity and
steps (all steps for current definition and start step in particular). Also has reference on list of entity ACL permissions.

**Methods:**
* **addStep(WorkflowStep)** - add step to steps array;
* **removeStep(WorkflowStep)** - remove step from steps array;
* **hasStepByName(stepName)** - check is there step with given name for workflow definition;
* **getStepByName(stepName)** - returns step by name if it exist, otherwise returns null;
* **addEntityAcl(WorkflowEntityAcl)** - add ACL rule to rules array;
* **removeEntityAcl(WorkflowEntityAcl)** - remove ACL rule from rules array;
* **hasEntityAclByAttributeStep(attributeStep)** - check is there attribute step has ACL rules for related entity;
* **getEntityAclByAttributeStep(attributeStep)** - returns ACL rules for related entity by attribute step if any;
* **import(WorkflowDefinition)** - import data from passed workflow definition into the current;
* **getObjectIdentifier()** - returns a unique identifier for this domain object;

Workflow Item
-------------
**Class:**
Oro\Bundle\WorkflowBundle\Entity\WorkflowItem

**Description:**
Specific instance of Workflow, contains state of workflow - data as instance of WorkflowData,
temporary storage of result of last applied transition actions as instance of WorkflowResult, current step name,
name of WorkflowDefinition, has reference to related Workflow Definition, Transition Records and Entity,
list of ACL identities for related entities, log of all applied transitions as list of WorkflowTransitionRecord entities,
contains serialized data of WorkflowItem, available instance of WorkflowAwareSerializer and serialize format parameter.

**Methods:**
* **addTransitionRecord(WorkflowTransitionRecord)** - add transition to transitions array;
* **addEntityAcl(WorkflowEntityAclIdentity)** - add ACL identity to array of workflow entity ACL identities;
* **removeEntityAcl(WorkflowEntityAclIdentity)** - remove ACL identity from array of workflow entity ACL identities;
* **hasAclIdentityByAttribute(attributeStep)** - check is there attribute step has ACL rules identities for related entity;
* **getAclIdentityByAttributeStep(attributeStep)** - returns ACL identities for related entity by attribute step if any;

Workflow Item Repository
------------------------
**Class:**
Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository

**Methods:**
* **findByEntityMetadata(entityClass, entityIdentifier)** - returns list of all Workflow Items related to input parameters;
* **getByDefinitionQueryBuilder(WorkflowDefinition)** - returns instance of QueryBuilder based on input workflow
definition parameters;
* **getEntityWorkflowStepUpgradeQueryBuilder(WorkflowDefinition)** - returns instance of QueryBuilder for related Entity
and herewith updated workflow step by input WorkflowDefinition start step;
* **resetWorkflowData(WorkflowDefinition, batchSize)** - perform reset workflow items data for given definition.
Optional you can control the size of batch, which will be reseted in the single query.

Workflow Step
-------------
**Class:**
Oro\Bundle\WorkflowBundle\Entity\WorkflowStep

**Description:**
This class is the representation of Step entity, it stores only data that be used in DB requests: name, label,
step order and final flag. Also Workflow Step knows about Workflow Definition that it attached to.

**Methods:**
* **import(WorkflowStep)** - copies data from source Workflow Step to current one;

Workflow Transition Record
--------------------------
**Class:**
Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord

**Description:**
Stores data about transitions: step form, step to, transition name and timestamp. Transition record is attached to
one specific Workflow Item.

Workflow Entity Acl
-------------------
**Class:**
Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl

**Description:**
Class represents entity ACL for specific attribute on specific step. It stores attribute name, Workflow Step entity,
entity class name and updatable/deletable flags. Also it has relation to Workflow Definition that uses this ACL.

**Methods:**
* **getAttributeStepKey()** - builds unique key based on attribute and step names to merge Workflow Entity Acl entities;
* **import(WorkflowEntityAcl)** - copies data from source Workflow Entity Acl to current one;

Workflow Entity Acl Identity
----------------------------
**Class:**
Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity

**Description:**
Stores relation between Workflow Entity Acl, specific entity (class and identifier) and Workflow Item.

**Methods:**
* **getAttributeStepKey()** - builds unique key based on attribute and step names to merge Workflow Entity Acl entities;
* **import(WorkflowEntityAclIdentity)** - copies data from source Workflow Entity Acl Identity to current one;

Workflow Entity Acl Identity Repository
---------------------------------------

**Class:**
Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclIdentityRepository

**Methods:**
* **findByClassAndIdentifier(class, identifier)** - returns list of all Acl Identities related to the specified entity;

Support Entities
================

Workflow Manager
----------------
**Class:**
Oro\Bundle\WorkflowBundle\Model\WorkflowManager

**Description:**
Main entry point for client to work with workflows. Provides lots of useful methods that should be used in controllers
and specific implementations. Injected ManagerRegistry, WorkflowRegistry, DoctrineHelper and ConfigManager.

**Methods:**
* **getStartTransitions(workflow)** - returns list of start transition of specified workflow;
* **getTransitionsByWorkflowItem(WorkflowItem)** - get list of all possible (allowed and not allowed) transitions
for specified WorkflowItem;
* **isTransitionAvailable(WorkflowItem, transition, errors)** - check if current transition is allowed for
specified workflow item, optionally returns list of errors;
* **isStartTransitionAvailable(workflow, transition, entity, data, errors)** - check whether specified start transition
is allowed for current workflow, optionally returns list of errors;
* **resetWorkflowItem(WorkflowItem)** - Perform reset of workflow item data - set $workflowItem and $workflowStep
references into null and remove workflow item. If active workflow definition has a start step,
then active workflow will be started automatically;
* **startWorkflow(workflow, entity, transition, data)** - start workflow for input entity using start transition
and workflow data as array;
* **massStartWorkflow(data)** - starts several workflows in one transaction, receives set of array that contains
workflow identifier, entity, transition (optional) and workflow data (optional);
* **transit(WorkflowItem, transition)** - perform transition for specified workflow item;
* **getApplicableWorkflow(entity)** - returns active workflow by related entity object;
* **getApplicableWorkflowByEntityClass(entityClass)** - returns active workflow by related entity class;
* **hasApplicableWorkflowByEntityClass(entityClass)** - check there entity class has active workflow;
* **getWorkflowItemByEntity(entity)** - returns Workflow Definition by related entity object;
* **getWorkflow(workflowIdentifier)** - get workflow instance by workflow name, workflow instance of workflow item or by
workflow itself;
* **activateWorkflow(workflowIdentifier)** - perform activation workflow by workflow name, Workflow instance,
WorkflowItem instance or WorkflowDefinition instance;
* **deactivateWorkflow(entityClass)** - perform deactivation workflow by entity class;
* **resetWorkflowData(WorkflowDefinition)** - perform reset workflow items data for given workflow definition;
* **isResetAllowed(entity)** - check that entity workflow item is equal to the active workflow item;

Workflow Data
-------------
**Class:**
Oro\Bundle\WorkflowBundle\Model\WorkflowData

**Description:**
Container for all Workflow data, implements ArrayAccess, IteratorAggregate and Countable interfaces.

Workflow Result
---------------
**Class:**
Oro\Bundle\WorkflowBundle\Model\WorkflowResult

**Description:**
Container of results of last applied transition actions. This data is not persistable so it can be used only once
right after successful transition.

Step Manager
-----------
**Class:**
Oro\Bundle\WorkflowBundle\Model\StepManager

**Description:**
StepManaged is a container for steps, is provides getters, setters and list of additional functions applicable to steps.

**Methods:**
* **getOrderedSteps()** - get list of steps sorted by rendering order.

Transition Manager
-----------------
**Class:**
Oro\Bundle\WorkflowBundle\Model\TransitionManager

**Description:**
TransitionManager is a container for transitions, is provides getters, setters
and list of additional functions applicable to transitions.

**Methods:**
* **extractTransition(transition)** - converts transition name to transition instance;
* **getStartTransitions()** - get list of start transitions;
* **getDefaultStartTransition()** - get default start transition that leads to the start step.

Attribute Manager
----------------
**Class:**
Oro\Bundle\ActionBundle\Model\AttributeManager

**Description:**
AttributeManager is a container for attributes, is provides getters, setters
and list of additional functions applicable to attributes.

**Methods:**
* **getEntityAttribute()** - attribute used as root entity;
* **getAttributesByType(type)** - get all attributes that have specified type;
* **getEntityAttributes()** - get all attributes that have type "entity".

Context Accessor
----------------
**Class:**
Oro\Component\Action\Model\ContextAccessor

**Description:**
Context is used in action and conditions and thereby it's usually an instance of Workflow Item.
This class is a simple helper that encapsulates logic of accessing properties of context using
Symfony\Component\PropertyAccess\PropertyAccessor.

ACL Manager
-----------

**Class:**
Oro\Bundle\WorkflowBundle\Acl\AclManager

**Description:**
Additional service that process ACL for workflow definitions and calculate Acl Identity entities for specified
Workflow Item.

Workflow Entity Voter
---------------------

**Class:**
Oro\Bundle\WorkflowBundle\Acl\Voter\WorkflowEntityVoter

**Description:**
Symfony ACL Voter that processes ACL for attributes and steps. Voter checks whether source entity has Workflow ACL
Identity and returns ACCESS_GRANTED or ACCESS_DENIED. If entity is not supported or it doesn't have identity -
returns ACCESS_ABSTAIN.

Workflow Configuration
----------------------
**Class:**
Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration

**Description:**
Contains tree builder for single Workflow configuration with steps, conditions, condition definitions, transitions.

**Methods:**
* **getConfigTreeBuilder()** - configuration tree builder for single Workflow configuration.

Workflow List Configuration
---------------------------
**Class:**
Oro\Bundle\WorkflowBundle\Configuration\WorkflowListConfiguration

**Description:**
Contains tree builder for list of Workflows, processConfiguration raw configuration of Workflows.

**Methods:**
* **getConfigTreeBuilder()** - configuration tree builder for list of Workflows.
* **processConfiguration(configs)** - processes raw configuration according to configuration tree builder

Workflow Configuration Provider
-------------------------------
**Class:**
Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider

**Description:**
Parses files workflow.yml in all bundles and processes merged configuration using Workflow List Configuration.

**Methods:**
* **getWorkflowDefinitionConfiguration()** - get list of configurations for Workflow Definitions.

Workflow Definition Configuration Builder
-----------------------------------------
**Class:**
Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder

**Description:**
Builds WorkflowDefinition entities based on input configuration (usually parsed from *.yml files).

**Methods:**
* **buildFromConfiguration(configurationData)** - build several entities based on configuration;
* **buildOneFromConfiguration(name, configuration)** - builds one entity based on definition name and configuration.

Workflow Data Serializer
------------------------
**Interface:**
Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer

**Class:**
Oro\Bundle\WorkflowBundle\Serializer\WorkflowDataSerializer

**Description:**
Extends standard Symfony Serializer to support Workflow entities.

Workflow Data Normalizer
------------------------
**Class:**
Oro\Bundle\WorkflowBundle\Serializer\Normalizer\WorkflowDataNormalizer

**Description:**
Custom data normalizer for Workflow Data Serializer, use basic serializer and collection of Attribute Normalizers.

**Methods:**
* **normalize(object, format, context)** - convert origin source data to scalar/array representation;
* **denormalize(data, class, format, context)** - convert scalar/array data to origin representation.

Attribute Normalizer
--------------------
**Interface:**
Oro\Bundle\WorkflowBundle\Serializer\Normalizer\AttributeNormalizer

**Description:**
Responsible for converting attribute values to scalar/array representation and vice versa. By default there are
two specific Attribute Normalizers: StandardAttributeNormalizer and EntityAttributeNormalizer. Any other can be
used with OroWorkflowBundle, use "oro_workflow.attribute_normalizer" tag to register your custom normalizers.

**Methods:**
* **normalize(Workflow, Attribute, attributeValue)** - convert Workflow Attribute value to scalar/array representation;
* **denormalize(Workflow, Attribute, attributeValue)** - convert Workflow Attribute value to original representation.
* **supportsNormalization(Workflow, Attribute, attributeValue)** - checks if normalization is supported
* **supportsDenormalization(Workflow, Attribute, attributeValue)** - checks if denormalization is supported

Parameter Pass
--------------
**Interface:**
Oro\Bundle\WorkflowBundle\Model\Pass\PassInterface

**Class:**
Oro\Bundle\WorkflowBundle\Model\Pass\ParameterPass

**Description:**
Passes through configuration and replaces access properties (f.e. $property) with appropriate PropertyPath instances.

**Methods:**
* **pass(data)** - replaces access properties with Property Path instances.
