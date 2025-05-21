The upgrade instructions are available at [Oro documentation website](https://doc.oroinc.com/master/backend/setup/upgrade-to-new-version/).

The current file describes significant changes in the code that may affect the upgrade of your customizations.

## UNRELEASED

### Added

### Layout Component
* Added `Oro\Component\Layout\Extension\Theme\Model\ThemeManagerInterface` interface that represents a manager for layout themes.
* Added `ThemeConfigOptionGetEvent` and `ThemeOptionGetEvent` events that are fired when a theme option or theme config value is retrieved.
* Added new `getThemeConfigOption` and `getThemeOption` methods in `Oro\Component\Layout\Extension\Theme\Model\ThemeManager` for retrieving inherited values.

#### LayoutBundle
* Added a new `fonts` configuration to the theme definition for building fonts with Webpack and referencing preloaded fonts.
* Added a new `preload_web_link` layout block type that uses Symfony WebLink component and pushes resources to clients before they even know that they need them.
* Added a new `oro_layout.inherited_theme_options` configs to application settings.

### Removed

#### LayoutBundle
* Removed `oro_layout.provider.svg_icons_support` service, now it should be used `getThemeOption` method from `oro_layout.theme_manager` service.

## Changes in the Platform package versions

- [6.1.0](#610-2025-03-31)
- [6.0.0](#600-2024-03-30)
- [5.1.0](#510-2023-03-31)
- [5.0.0](#500-2022-01-26)
- [4.2.10](#4210)
- [4.2.4](#424)
- [4.2.2](#422)
- [4.2.0](#420-2020-01-29)
- [4.1.0](#410-2020-01-31)
- [4.0.0](#400-2019-07-31)
- [3.1.4](#314)
- [3.1.3](#313-2019-02-19)
- [3.1.2](#312-2019-02-05)
- [3.1.0](#310-2019-01-30)
- [3.0.0](#300-2018-07-27)
- [2.6.0](#260-2018-01-31)
- [2.5.0](#250-2017-11-30)
- [2.2.0](#220-2017-05-31)
- [2.1.0](#210-2017-03-30)

## 6.1.0 (2025-03-31)
[Show detailed list of changes](incompatibilities-6-1.md)

### Security Changes
* Security firewall `wsse_secured` renamed to `old_api_secured`.
* Security firewall `api_wsse_secured` renamed to `api_secured`.

### Added

#### DistributionBundle
* Added the ability to run post-install and post-update composer scripts defined in the composer.json of a dependency. Script that should be run during post-install must be put into section `extra.oro-post-install-cmd`, during post-update - into section `extra.oro-post-update-cmd`.
* Added new event `oro_distribution.route_collection.all` dispatched when routes are loaded from all route loaders. Implemented via `\Oro\Bundle\DistributionBundle\Routing\DelegatingLoader` decorating the `routing.loader` service.
* Added `\Oro\Bundle\DistributionBundle\EventListener\RoutePrioritizingListener` as a general solution to alter route priorities.
* Added `oro_distribution.event_listener.route_prioritizing.web_debug_toolbar` to increase the priorities of web debug toolbar routes (_wdt, _profiler*, _preview_error).
* Added the ability to override controller template attribute with `_template_override` request attribute. Useful for overriding the template of a forwarded request. Implemented in `\Oro\Bundle\DistributionBundle\EventListener\ControllerTemplateListener`.

#### FormBundle
* Added the ability to use nested constraints (e.g. `When`) in `\Oro\Bundle\FormBundle\Validator\ConstraintFactory` that creates constraints from an array definition (e.g. fetched from `system_configuration.yml`).
* Added the ability to use the `time` HTML tag with a `datetime` attribute in `html_purifier_modes`.

#### ThemeBundle
* Added theme configuration feature that provides theme developers a way to make a storefront theme configurable by a store owner.
* Added new `\Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration` entity that contains theme configuration options.
* Added new `oro:theme:configuration:validate` command that validates theme configuration.
* Added `\Oro\Bundle\ThemeBundle\Fallback\Provider\ThemeConfigurationFallbackProvider` fallback provider which fetches data from theme configuration.
* Added `\Oro\Bundle\ThemeBundle\Acl\Voter\ThemeConfigurationDependencyDeleteVoter` voter that forbids removal operation for dependent entities if they are used in theme configuration.
* Added a new `getThemeConfigurationOptionsNamesByType` method for `\Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider` provider that returns all theme options names by type of option.

#### UIBundle
* Added new twig function `oro_is_string` that finds whether the given variable type is a string.
* Added new twig `html_controls_after` variable for `form_row` widget that renders passed html content.
* Removed jquery-numeric

#### LayoutBundle
* Added new twig function `oro_theme_configuration_value` that returns theme configuration option value.
* Added theme configuration definition for configuration section into `\Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration`.

#### AttachmentBundle
* Added new `\Oro\Bundle\AttachmentBundle\Form\Type\ContentFileType` form type that saves content from the uploaded file but does not save file in the system.

#### SecurityBundle
* Added new `\Oro\Bundle\SecurityBundle\DoctrineExtension\Dbal\Types\CryptedTextType` doctrine type that stores text data in crypted format.
* Decomposed `\Oro\Bundle\SecurityBundle\Request\SessionHttpKernelDecorator` into `\Oro\Bundle\SecurityBundle\Request\SessionStorageOptionsManipulator`.
* Added `oro_security.session.storage.options` container parameters that holds original session storage options.

#### ApiBundle
* Added new `crypted_text` doctrine type as a data type to `api_doc_data_types` and `open_api` sections of ORO API configuration.
* Added an "Add filter" button to each resource for API sandbox.
* Added a new meta `validate` flag to create and update operations, making API requests with transaction rollback instead of commit.
* Added a new `rollback_validated_request` event for `customize_form_data` action.

#### ChartBundle
* Chart scripts are declared as `dynamic-imports` scripts for the storefront.
* Added a new `purchase-volume-chart` chart.

#### DataGridBundle
* Added a new `transTemplate` datagrid option for the `itemsCounter` section that allows to set translation template for the datagrid items counter.
* Added a new `orodatagrid/js/datagrid/builder/items-count-external` datagrid builder that allows the showing of datagrid item count in an element with the specific data attribute.

### Changed

#### ActionBundle

##### Operations

- added ability to define operation logic as a service instead of having it in YAML
- Added `OperationServiceInterface` and `OperationServiceAbstract` for operation services
- added new tag `oro_operation_service` to gather operation services in a separate service locator
- updated OperationAssembler and Operation to work with services
- Moved acl_resource check to the announce event listener instead of constantly adding it to pre-conditions
- Moved operation feature availability check to the announce event listener instead of constantly adding it to pre-conditions

###### Operation Events

Operation execution events added for better extensibility and control of operation execution flow

- `announce` event is triggered during checking pre-conditions
- `guard` event is triggered during checking conditions
- `pre_execute` and `execute` events are triggered before and after operation logic execution

For all event types 2 events are triggered: `oro_operation.<event_name>` and `oro_operation.<operation_name>.<event_name>`.

##### Action Group Changes

* Added `ActionExecutor` to be able to run actions and action groups from PHP code without a need to work with ActionData, context, PropertyPaths, etc.
* Added `ActionGroupWrapper` to be able to call a service method as an action group. All such services should be added to service locator with tag `oro_action_group_service`. If service needs to add/change some context variables it has to return an array with an appropriate keys. Note that argument names are used to map data from context to method arguments and must be named same to call parameters.
* Moved logic from action groups YAML definitions to PHP services for checkout-related action_groups
* Added ability to define action group as a service + method config parameters
* added `return_value_name` for `action_group` to map value to action group result correctly for action group services
* added `method_argument_name` to parameter config to map action group parameter to service method argument
* Moved `acl_resource` check to the guard event listener instead of constantly adding it to pre-conditions (this check was lost for service-based action_groups)
* Action group execution events added for better extensibility and control of execution flow

  * `guard` event is triggered during checking conditions
    *`pre_execute` and `execute` events are triggered before and after operation logic execution

For all event types 2 events are triggered. `oro_action_group.<event_name>` and `oro_action_group.<operation_name>.<event_name>`

#### WorkflowBundle

* added `conditional_steps_to` to allow to transit workflow to different steps with single transition based on condition per-step
* `conditional_steps_to` are shown in workflow UI
* Improved workflow config importing, allowed to reference files in bundles with `@BundleName/path/to/configs/workflow.yml` syntax

##### Transition services

* added ability to define transition logic as a transition service instead of having it in YAML transition_definition
* Added `TransitionServiceInterface` and `TransitionServiceAbstract` for transition services
* added new tag `oro_workflow.transition_service` to gather transition services in a separate service locator
* updated `TransitionAssembler`, Transition and Workflow configuration to work with transition services
* implemented `ContinueToShippingAddress` for b2b checkout workflow as a transition service example
* added evaluateExpression to ActionExecutor service to cover whole transition needs: run an action, action group, evaluate condition expression
* Improved QuoteAcceptable condition to support default as a property path

##### Workflow Events

Added workflow events
* `pre_announce`, `announce`. These events are triggered during checking pre-conditions
* `pre_guard`, `guard`. These events are triggered during checking conditions

Next event are triggered during transition execution:
* `start` (workflow)
* `leave` (step)
* `enter` (step)
* `entered` (step)
* `transition` (transition)
* `completed` (transition)

For all events except start 3 events are triggered. `oro_workflow.<event_name>`, `oro_workflow.<workflow_name>.<event_name>` and `oro_workflow.<workflow_name>.<event_name>.<step_name|transition_name>`

##### Workflow ACL checks

* Moved `acl_resource` check to pre_announce event listener instead of constantly adding it to pre-conditions
* Moved `is_granted_workflow_transition` check for steps to pre_announce event listener
* Moved `resolve_destination_page` from actions to oro_workflow.transition listener for all workflows to support usage of this option with transition_service
* ResolveDestinationPage was updated to support destination as variable in workflows
* `aclResource` and `aclMessages` were added to Transition model

##### Workflow Definition Load CLI command

* Added `--watch` and `--watch-interval` options to `oro:workflow:definition:load` command to periodically reload the workflow definition(s). Can be used during development to not bother the developer who might forget to reload the definition.

##### ExtendableCondition and ExtendableAction changes

* removed dependency on WorkflowItem/ActionData in listeners which limited those listeners to a place from which event is triggered (workflow definition or action group)
* extracted extendable condition errors processing to a separate service to not depend on the translator and request stack directly
* added ability to trigger checkout events directly using event dispatcher without a need to use extendable action|condition
* introduced determined set of data available in listeners, access to current execution context was limited in order to remove untraceable dependencies on the inner workflow implementations and improve listeners re-usability

##### Configuration import directive filters

* Added `import_condition` (Expression Language syntax same to on used in DI) option for workflow import
* Added import filters that may be registered via `WorkflowConfigurationImportsProcessor::addImportFilter` and should implement the `ImportFilterInterface`


#### ThemeBundle
* Changed a field value of the `type` field on a string instead of the removed `\Oro\Bundle\ThemeBundle\Entity\Enum\ThemeConfigurationType` enum for `\Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration` entity.

#### ApiBundle
* The `masterRequest` attribute in API context was replaced with `mainRequest`. If you have API processors that use the `masterRequest` attribute in the `oro.api.processor` service tag it should be replaces with the `mainRequest` attribute.
* Changed `\Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface` to be able to use filters by same field but with different operators, e.g. `?filter[id][gt]=1&filter[id][lt]=10`:
  - added `getOne` method
  - changed return value for `get` method from `?FilterValue` to `FilterValue[]`
  - changed return value for `getGroup` method from `array [filter key => FilterValue, ...]` to `array [filter key => [FilterValue, ...], ...]`
  - changed return value for `getAll` method from `array [filter key => FilterValue, ...]` to `array [filter key => [FilterValue, ...], ...]`
* Outdated format of API filters like `filter[id]>=1` is not supported from now, use `filter[field][operator]=value` syntax instead, e.g. `filter[id][gte]=1`.

#### ActionBundle

##### Action Group Changes

* Added ActionExecutor to be able to run actions and action groups from PHP code without a need to work with ActionData, context, PropertyPaths, etc.
* Added ActionGroupWrapper to be able to call a service method as an action group. All such services should be added to service locator with tag oro_action_group_service. If service needs to add/change some context variables it has to return an array with an appropriate keys. Note that argument names are used to map data from context to method arguments and must be named same to call parameters.
* Moved logic from action groups YAML definitions to PHP services for checkout-related action_groups
* Added ability to define action group as a service + method config parameters
* Added `return_value_name` for action_group to map value to action group result correctly for action group services
* Added `method_argument_name` to parameter config to map action group parameter to service method argument

#### WorkflowBundle

* Added `conditional_steps_to` to allow to transit workflow to different steps with single transition based on condition per-step
* `conditional_steps_to` are shown in workflow UI
* Improved workflow config importing, allowed to reference files in bundles with `@BundleName/path/to/configs/workflow.yml` syntax
* Disallowed to transit workflow in pre-conditions or pre-actions.

##### Transition services

* Added ability to define transition logic as a transition service instead of having it in YAML transition_definition
* Added TransitionServiceInterface and TransitionServiceAbstract for transition services
* Added new tag oro_workflow.transition_service to gather transition services in a separate service locator
* Updated TransitionAssembler, Transition and Workflow configuration to work with transition services
* Implemented ContinueToShippingAddress for b2b checkout workflow as a transition service example
* Added evaluateExpression to ActionExecutor service to cover whole transition needs: run an action, action group, evaluate condition expression
* Improved QuoteAcceptable condition to support default as a property path

##### Workflow Events

Added workflow events
* pre_announce, announce. These event are triggered during checking pre-conditions
* pre_guard, guard. These event are triggered during checking pre-conditions

Next event are triggered during transition execution:
* start (workflow)
* leave (step)
* enter (step)
* entered (step)
* transition (transition)
* completed (transition)

For all events except start 3 events are triggered. oro_workflow.<event_name>, oro_workflow.<workflow_name>.<event_name> and oro_workflow.<workflow_name>.<event_name>.<step_name|transition_name>

##### Workflow ACL checks

* Moved `acl_resource` check to pre_announce event listener instead of constantly adding it to pre-conditions. After this change, `acl_resource` checks will be done *before* pre-actions. It's no longer possible to reference variables in `acl_resource`, such checks must be moved to pre-conditions.
* Moved `is_granted_workflow_transition` check for steps to `pre_announce` event listener
* Moved `resolve_destination_page` from actions to `oro_workflow.transition` listener for all workflows to support usage of this option with transition_service
* `ResolveDestinationPage` was updated to support destination as variable in workflows
* `aclResource` and `aclMessages` were added to Transition model

##### Workflow Definition Load CLI command

* Added `--watch` and `--watch-interval` options to `oro:workflow:definition:load` command to periodically reload the workflow definition(s). Can be used during development to not bother the developer who might forget to reload the definition.

##### ExtendableCondition and ExtendableAction changes

* ExtendableCondition does not work directly with context, accepting only the data needed for event listeners.
* ExtendableConditionEvent `getContext` replaced by `getData`. It's no longer possible to work directly with the workflow/action execution context.
* ExtendableAction does not work directly with context, accepting only the data needed for event listeners.
* ExtendableActionEvent `getContext` replaced by `getData`. It's no longer possible to work directly with the workflow/action execution context.
* Introduced determined set of data available in listeners, access to current execution context was limited in order to remove untraceable dependencies on the inner workflow implementations and improve listeners re-usability
* Removed dependency on WorkflowItem/ActionData in listeners which limited those listeners to a place from which event is triggered (workflow definition or action group)
* Extracted extendable condition errors processing to a separate service to not depend on the translator and request stack directly
* Added ability to trigger checkout events directly using event dispatcher without a need to use extendable action|condition

##### Configuration import directive filters

* Added `import_condition` (Expression Language syntax same to on used in DI) option for workflow import
* Added import filters that may be registered via `WorkflowConfigurationImportsProcessor::addImportFilter` and should implement the `ImportFilterInterface`

#### EntityBundle
* Changed `\Oro\Bundle\EntityBundle\Provider\EntityNameProvider` to make it work with enum fields.

#### EmailBundle
* Fixed `\Oro\Bundle\EmailBundle\Sync\NotificationAlertManager` fails to update an existing alert if user is null.

#### UserBundle
* Changed `\Oro\Bundle\UserBundle\Controller\ResetController::sendEmailAction` so it redirects to the `_return_route` route request attribute in case of error.
* Changed `\Oro\Bundle\UserBundle\Controller\ResetController::sendEmailAction` so it redirects to the `_target_route` request attribute in case of success.
* Changed `@OroUser/Reset` templates to enable the ability to override form action path, return path and target path.
* Changed `@OroUser/Security/login.html.twig` template to enable the ability to override form action path, return path and target path.

#### SanitizeBundle
* Updated sanitize logic, it takes into account a new `crypted_text` doctrine type now.

#### ActionBundle
* Disabled key normalization for `data` attributes of the `button_options` section for operations. Now, using the dash symbol for data attribute naming is possible.

# Oro Enum Changes

Updating the ORO Enum implementation is caused by:

- Read-only permissions for web server user on entity extend files in the system cache.
- Updates to entities from the UI do not require cache updates in the entity management.
- Need to avoid code generation.
- Reduce the number of requests in cases where the object uses join queries with a large number of lists.
- After completion, it will be possible to remove MultiHostBundle on schema update.

## The structure of relationships between entities and storage

*Before:*

- The main storage place for ORO Enums are tables created for each enum. The table name begins with ``oro_enum_``.

Example:

```
oro_enum_auth_status
oro_enum_inventory_status
```

- The connection between a specific enum and a table is created using a relation column. To get the value of the enum,
  you need to make a join in the query.
- The entity configuration stores the relationship between the enum and the target table and the configuration of the
  enum itself.
- There is a difference in configuration for the enum and for the product attribute of the enum.
- All extend enums are inherited from the base one ```Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue```.

*After:*

- All ORO enums are stored in a table ```oro_enum_option```.
- The connection between the target table that uses the enum and the enum itself is built without a relation. Enum
  values used by entities are stored in the field ```serialized_data```.

- Updated enums are inherited not from the ```Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue```
  but from ```Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface```[[f]](#upgrade-toolkit-coverage)

``` json
{
    "inventory_status": "prod_inventory_status.in_stock",
    "multi_enum": ["prod_multi_enum.test_option_1", "prod_multi_enum.test_option_3"]
}
```
## The structure of DB storage

*Before:*

Stored in the generated table, which begins with the prefix "oro_enum_" (that is, each enum has its own table).

Example of enum for "oro_enum_auth_status" table:

```markdown
| id      | name    | priority | is_default |
| reset   | Reset   | 2        | false      |
| locked  | Locked  | 3        | false      |
| expired | Expired | 4        | false      |
| active  | Active  | 1        | true       |
```

*After:*

Stored in the "oro_enum_option" table.

Example:

```markdown
| id                  | internal_id | enum_code   | name    | priority | is_default |
| auth_status.locked  | locked      | auth_status | Locked  | 3        | false      |
| auth_status.expired | expired     | auth_status | Expired | 4        | false      |
| auth_status.active  | active      | auth_status | Active  | 1        | true       |
| auth_status.reset   | reset       | auth_status | Reset   | 2        | false      |
```

## Versioned migration Enum changes:

*Before:*

```php
    $queries->addPostQuery(
        new OutdatedInsertEnumValuesQuery(
            $this->extendExtension,
            'cu_auth_status', 
            [new OutdatedEnumDataValue(CustomerUserAuthStatus::STATUS_EXPIRED, 'Expired', 3)]
        )
     );
```

```php
namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_0;

class OroCalendarBundle implements Migration, ExtendExtensionAwareInterface
 {
     use ExtendExtensionAwareTrait;
 
     public function up(Schema $schema, QueryBag $queries)
     {
         $table = $schema->getTable('oro_calendar_event_attendee');
        $this->extendExtension->addEnumField(
            $schema,
            $table,
            'status',
            'ce_attendee_status',
            false,
            false,
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM]
            ]
         );
     }
```

*After:*

```php
    $queries->addPostQuery(
        new OutdatedInsertEnumValuesQuery(
            $this->outdatedExtendExtension, // Outdated extend extension must be used for old enums
            'cu_auth_status', 
            [new OutdatedEnumDataValue(CustomerUserAuthStatus::STATUS_EXPIRED, 'Expired', 3)]
        )
     );
```

```php
namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_0;

class OroCalendarBundle implements Migration, OutdatedExtendExtensionAwareInterface
 {
     use OutdatedExtendExtensionAwareTrait;
 
    public function up(Schema $schema, QueryBag $queries)
     {
         $table = $schema->getTable('oro_calendar_event_attendee');
        $this->outdatedExtendExtension->addOutdatedEnumField(
            $schema,
            $table,
            'status',
            'ce_attendee_status',
            false,
            false,
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM]
            ]
         );
     }
```

## Migration data fixture:

*Before:*

```php
   $queries->addPostQuery(new OutdatedInsertEnumValuesQuery($this->outdatedExtendExtension, 'cu_auth_status', [
            new OutdatedEnumDataValue(CustomerUserAuthStatus::STATUS_EXPIRED, 'Expired', 3)
        ]));
```

*After:*

```php
class LoadLeadStatusOptionData extends AbstractEnumFixture
{
    protected function getData(): array
    {
        return [
            'new' => 'New',
            'qualified' => 'Qualified',
            'canceled' => 'Disqualified',
        ];
    }

    protected function getDefaultValue(): string
    {
        return 'new';
    }

    protected function getEnumCode(): string
    {
        return 'lead_status';
    }
}

```

## Joining enum options DQL:

*Before:*

- join Enum options via "relation"

```php
    ->leftJoin('attendee.status', 'attendee_status')
```

*After:*

```php
->leftJoin(
    EnumOption::class,
    'attendee_status',
    Expr\Join::WITH,
    "JSON_EXTRACT(attendee.serialized_data, 'status') = attendee_status"
)

```

- Query by enum fields

```php
 $qb = $this->doctrine->getRepository(CustomerUser::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('IDENTITY(u.auth_status) <> :authStatus')
```

*After:*

```php
 $qb = $this->doctrine->getRepository(CustomerUser::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere("JSON_EXTRACT(u.serialized_data, 'auth_status') <> :authStatus")
```

## Query to Enum Option Repository:

*Before:*

```php
   $statusEnum = $this->doctrine
        ->getRepository(ExtendHelper::buildEnumValueClassName(Attendee::STATUS_ENUM_CODE))
        ->find(Attendee::STATUS_NONE);
```

*After:*

```php
    $statusEnum = $this->doctrine
         ->getRepository(EnumOption::class)
         ->find(ExtendHelper::buildEnumOptionId(Attendee::STATUS_ENUM_CODE, Attendee::STATUS_NONE)
    );
```

## Comparison of enum option:

*Before:*

```php
  if ($user->getAuthStatus()->getId() !== UserManager::STATUS_ACTIVE) {}
```

```php
  if (!$originalValue instanceof AbstractEnumValue) {}
```

*After:*

```php
  if ($user->getAuthStatus()->getIntenalId() !== UserManager::STATUS_ACTIVE) {}
```

```php
  if (!$originalValue instanceof EnumOptionInterface) {}
```

### Expression Language changes:

*Before:*

```yaml
    shippingRuleD:
      name: 'D'
      enabled: true
      sortOrder: 1
      stopProcessing: true
      expression: "lineItems.any( lineItem.product.shipping_category.id = 'd' )"
```

*After:*

```yaml
    shippingRuleD:
        name: 'D'
        enabled: true
        sortOrder: 1
        stopProcessing: true
        expression: "lineItems.any( lineItem.product.shipping_category.internalId = 'd' )" # use internalId instead of id
```

### Datagrids changes:[[p]](#upgrade-toolkit-coverage)

*Before:*

- join enum options

```yaml
join:
  left:
    - { join: ra.status, alias: status }
```

*After:*

```yaml
join:
  left:
#    - { join: ra.status, alias: status } # removed
```

- Example of datagrid columns and joins update

*Before:*

```yaml
    orders-grid:
        acl_resource: oro_order_view
        extends: base-orders-grid
        source:
            query:
                select:
                    - internalStatus.name as internalStatusName
                    - internalStatus.id as internalStatusId
                join:
                    left:
                        - { join: order1.internal_status, alias: internalStatus }
                groupBy:  order1.id
            hints:
                - HINT_TRANSLATABLE
        columns:
            internalStatusName:
                label: oro.order.internal_status.label
```

*After:*

```yaml
    orders-grid:
        acl_resource: oro_order_view
        extends: base-orders-grid
        source:
            query:
                select:
#                    - internalStatus.name as internalStatusName # removed
#                    - internalStatus.id as internalStatusId # removed
                join:
                    left:
#                       - { join: order1.internal_status, alias: internalStatus } # removed
                groupBy:  order1.id
            hints:
                - HINT_TRANSLATABLE
        columns:
            internal_status:
                label: oro.order.internal_status.label
```

### Data fixture changes:[[p]](#upgrade-toolkit-coverage)

- load enum options

*Before:*

```yaml
Extend\Entity\EV_Acc_Internal_Rating:
    1_of_5:
        __construct:
            - '1_of_5'
            - '1_of_5'
```
*After:*

```yaml
Oro\Bundle\EntityExtendBundle\Entity\EnumOption:
    1_of_5:
        __construct:
            - 'acc_internal_rating' # enum_code
            - '1_of_5'
            - '1_of_5'
```

## Search index

- Enum fields do not change in the search index, the only thing you need to remember is that in the search, the internal id of the enum is used as a key, and the value remains unchanged.

## Import and Export

- The format of import and export has not changed, but you need to remember that the internalId of the enum option is used as an identifier.

### Transition definition configuration changes:

*Before:*

```yaml
quote_creating_definition:
  preconditions:
    '@and':
         - '@neq':  [$status.id, 'lost']
         - '@neq':  [$status.id, 'won']
         - '@type': [$customer.target, 'Oro\Bundle\CustomerBundle\Entity\Customer']

```
*After:*

```yaml
quote_creating_definition:
  preconditions:
    '@and':
      - '@neq':  [$status.internalId, 'lost']
      - '@neq':  [$status.internalId, 'won']
      - '@type': [$customer.target, 'Oro\Bundle\CustomerBundle\Entity\Customer']
```

### Checking the set of changes of enum field

*Before:*

```php
    $changeSet = $unitOfWork->getEntityChangeSet($entity);
    if (isset($changeSet['status']) || isset($changeSet['inventory_status'])) {}
```

*After:*

```php
    $changeSet = $unitOfWork->getEntityChangeSet($entity);
    if (isset($changeSet['serialized_data'][0]['status']) # $changeSet['serialized_data'][0] - previous status value
        || isset($changeSet['serialized_data'][0]['inventory_status'])) {}
```

### System configuration changes:

*Before:*

```php
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_seller_dashboard');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'order_count_status_criteria' => [
                    'type' => 'array',
                    'value' => [
                        OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
                    ]
                ]
            ]
        );
    }
```
*After:*

```php
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_seller_dashboard');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'order_count_status_criteria' => [
                    'type' => 'array',
                    'value' => [
                        ExtendHelper::buildEnumOptionId(
                            Order::INTERNAL_STATUS_CODE,
                            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
                        ),
                    ]
                ]
            ]
        );
    }
```

### Template changes (optional, to improve performance):

*Before:*

```html
<i {% if invitationClass %}class="{{ invitationClass }}" title="{{ attendee.status.name }}" {% endif %}></i>

```

*After:*

```html
<i {% if invitationClass %}class="{{ invitationClass }}" title="{{ attendee.status.id|trans_enum }}" {% endif %}></i>
```

### Behat test changes:

*Before:*

```text
 Scenario: Create Multi-Select fields with one auditable
   ...
   And I fill form with:
   | Field name    | AuditableMultiSelect |
   | Type          | Multi-Select         |
   And I click "Continue"
   And set Options with:
   | Label   |
   | Option1 |
   | Option2 |
   | Option3 |
   And I fill form with:
   | Auditable | Yes |
   And I save and create new form
   Then click update schema            # schema update step is needed
   When I click update schema
   Then I should see Schema updated flash message
   ...
```
*After:*

```text
Scenario: Create Multi-Select fields with one auditable
  ...
  And I fill form with:
  | Field name    | AuditableMultiSelect |
  | Type          | Multi-Select         |
  And I click "Continue"
  And set Options with:
  | Label   |
  | Option1 |
  | Option2 |
  | Option3 |
  And I fill form with:
  | Auditable | Yes |
  And I save and create new form
#  Then click update schema                # This step is not needed more for enums fields!!!
#  When I click update schema
#  Then I should see Schema updated flash message
  ...
```

### Removed

#### EntityBundle
* Removed **activity_support** configuration option from **dictionary** entity config scope. Consider replacing it with **collapsed_associations** configuration option of QueryDesignerBundle when it is used for your entity.

#### WsseAuthenticationBundle
* WsseAuthenticationBundle was removed. WSSE auth headers cannot be used for API. Use Oauth 2.0 instead.

## 6.0.0 (2024-03-30)
[Show detailed list of changes](incompatibilities-6-0.md)

### Security Changes

Based on symfony security changes, the approach to user authentication was updated. The main differences in the new approach will be described below.

* You should use `SecurityExtension::addAuthenticatorFactory()` instead of `SecurityExtension::addSecurityListenerFactory()`.[[f]](#upgrade-toolkit-coverage)

Before:

```php
$extension->addSecurityListenerFactory(new OrganizationFormLoginFactory());
```

After:

```php
$extension->addAuthenticatorFactory(new OrganizationFormLoginFactory());
```

* Authenticators have replaced the previous implementation of authentication using providers and listeners.

Before:

```text
Oro\Bundle\SecurityBundle\Http\Firewal\OrganizationBasicAuthenticationListener
Oro\Bundle\SecurityBundle\Authentication\Provider\UsernamePasswordOrganizationAuthenticationProvider
```

After:

```text
Oro\Bundle\SecurityBundle\Authentication\Authenticator\UsernamePasswordOrganizationAuthenticator
```

* You should use `security.access_control` rules instead of anonymous authenticator.

Before:

```yaml
security:
    firewalls:
        healthcheck_http_status_checks:
            pattern:   /healthcheck/http_status_check(s$|/.*)
            provider:  chain_provider
            anonymous: true # anonymous authentication is not available
```

After:

```yaml
security:
    firewalls:
        healthcheck_http_status_checks:
            pattern:   /healthcheck/http_status_check(s$|/.*)
            provider:  chain_provider

oro_security:
    access_control:
        - { path: /healthcheck/http_status_check(s$|/.*), roles: PUBLIC_ACCESS }
```

* Changed implementation of `anonymous_customer_user` authnetication. To check whether the user is authorized, it is not enough to check whether the user is absent in the token, it is also worth checking whether this user is not a customer visitor (`CustomerVisitor::class`).[[p]](#upgrade-toolkit-coverage)

Before:

```php
if ($this->getUser()) {
    # implementation
}
```

```php
if (null !== $this->getUser()) {
    # implementation
}
```

After:

```php
if ($this->getUser() instanceof AbstractUser) {
    # implementation
}
```

```php
if (!$this->getUser() instanceof CustomerUser) { 
    # implementation
}
```

* In twig templates, to check whether the user is authorized, you need to use the function `is_authenticated` that excludes from the check (anonymous customer user).

Before:

```text
{% if app.user is not null %}
    # implementation
{% endif %}
```

After:

```text
{% if is_authenticated() %}
    # implementation  
{% endif %}
```

* Added new `feature_firewall_authenticators` option to `api_firewalls` configuration to disable authenticator when some API feature is disabled.

Before:

```yaml
oro_api:
  api_firewalls:
    api_wsse_secured:
      feature_firewall_listeners:
        - Oro\Bundle\OAuth2ServerBundle\Security\Firewall\OAuth2Listener
    wsse_secured:
      feature_firewall_listeners:
        - Oro\Bundle\OAuth2ServerBundle\Security\Firewall\OAuth2Listener
    frontend_api_wsse_secured:
      feature_firewall_listeners:
        - Oro\Bundle\OAuth2ServerBundle\Security\Firewall\OAuth2Listener
```

After:

```yaml
oro_api:
  api_firewalls:
    api_wsse_secured:
      feature_firewall_authenticators: # FeatureDependAuthenticatorChecker
        - Oro\Bundle\OAuth2ServerBundle\Security\Authenticator\OAuth2Authenticator
    wsse_secured:
      feature_firewall_authenticators: # FeatureDependAuthenticatorChecker
        - Oro\Bundle\OAuth2ServerBundle\Security\Authenticator\OAuth2Authenticator
    frontend_api_wsse_secured:
      feature_firewall_authenticators: # FeatureDependAuthenticatorChecker
        - Oro\Bundle\OAuth2ServerBundle\Security\Authenticator\OAuth2Authenticator
```

### Added

#### ApiBundle
* String value(s) contains only space symbols is not allowed for string API filters. For example the following filters
  are not valid: `filter[code]= `, `filter[code]=code1, ,code2`. If by some reasons you want to allow value(s) contains
  only space symbols for some filters, you can use `allow_empty` filter option in `Resources/config/oro/api.yml`, e.g.:

```yaml
api:
  entities:
    Acme\Bundle\AcmeBundle\Entity\SomeEntity:
      filters:
        code:
          options:
            allow_empty: true
```
* Added the ability to specify `\Symfony\Component\Validator\Constraints\GroupSequence` in validation groups of API config via nested arrays.

#### FormBundle
* Added the chain of constraint converters to `\Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverter` with basic implementation in `\Oro\Bundle\FormBundle\Form\Extension\JsValidation\GenericConstraintConverter`.
* Added `\Oro\Bundle\FormBundle\Form\Extension\JsValidation\RangeConstraintConverter` for `Range` constraint with the ability to handle `minPropertyPath` and `maxPropertyPath`.
* Added `\Oro\Bundle\FormBundle\Validator\Constraints\AdaptivelyValidCollection` validation constraint for validating a collection of entities with different validation groups for the new, updated and unchanged elements.

#### PlatformBundle
* Added `\Oro\Bundle\PlatformBundle\Validator\Constraints\ValidEmbeddable` that allows to apply `Valid` constraint with explicit validation groups specified in `embeddedGroups` option.

#### EmailBundle
* Added the email template inheritance feature that provides an ability to extend an email template from parent one.
* Added `oro_email.email_template_wysiwyg_enabled` system config setting allowing to toggle WYSIWYG an Email Template create/edit page. Now WYSIWYG is turned off by default. It is not recommended to turn it on if you are using the email template inheritance feature as it may break template syntax.
* Added `\Oro\Bundle\EmailBundle\Provider\RenderedEmailTemplateProvider` as an entry point to get a rendered email template by criteria.
* Added `\Oro\Bundle\EmailBundle\Sender\EmailTemplateSender` as an entry point to render email template and send it to recipients.
* Added `\Oro\Bundle\EmailBundle\Factory\EmailModelFromEmailTemplateFactory` as an entry point to render an email template and create an email model ready to be sent.
* Added `\Oro\Bundle\EmailBundle\Provider\EmailTemplateContextProvider` that dispatches `\Oro\Bundle\EmailBundle\Event\EmailTemplateContextCollectEvent` to collect email template context (e.g. localization) needed for its searching and rendering.
* Added `\Oro\Bundle\EmailBundle\Controller\AjaxEmailController::compileEmailAction` to use it for email template rendering instead of `\Oro\Bundle\EmailBundle\Controller\Api\Rest\EmailTemplateController::getCompiledAction` in a Compose Email dialog.

#### EntityBundle
* Added method `getEntityTreeNodeByPropertyPath` to JS `EntityStructureDataProvider` (see [documentation](https://doc.oroinc.com/master/bundles/platform/EntityBundle/entity-structure-data-provider#get-entitytreenode-by-property-path))
* Added magic property `entityTree` to `EntityStructureDataProvider` (see [documentation](https://doc.oroinc.com/master/bundles/platform/EntityBundle/entity-structure-data-provider#entity-tree)) that allows walk through entity fields tree
* Added JS `EntityTreeNode` that is used in `EntityStructureDataProvider`

#### FormBundle
* Added JS `ExpressionEditorComponent` (see [documentation](https://doc.oroinc.com/master/bundles/platform/FormBundle/expression-editor#expressioneditorcomponent) that used instead regular `ViewComponent` in formtype options of rule editor. It's designed to prepare instance of `EntityStructureDataProvider` and create instance of `ExpressionEditorView`
  expression-editor-component.js
* Added `\Oro\Bundle\FormBundle\Validator\Constraints\UniqueEntity` validation constraint and validator.

#### UIBundle
* Added `renderCollapsibleWysiwygContentPreview` and `renderWysiwygContentPreview` TWIG macros to UIBundle for
  rendering WYSIWYG content in backoffice.

#### CurrencyBundle
* Added supporting of the `readonly` attribute in `\Oro\Bundle\CurrencyBundle\Form\Type\PriceType`.

### Changed

#### ApiBundle
* Removed support of `{@feature}` placeholder in API documentation. It was done because API documentation should not
  depend on the application configuration changes made by users.

#### EmailBundle
* Decomposed `\Oro\Bundle\EmailBundle\Provider\EmailRenderer`, made it as an entry point to render an email template.
  * Added `\Oro\Bundle\EmailBundle\Event\EmailTemplateRenderBeforeEvent` and `\Oro\Bundle\EmailBundle\Event\EmailTemplateRenderAfterEvent`
* Decomposed `\Oro\Bundle\EmailBundle\Form\Type\EmailType` into `\Oro\Bundle\EmailBundle\Form\EventListener\EmailTemplateRenderingSubscriber` to move the email template rendering responsibility to the form subscriber.
* Changed `\Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture` to make it ensure that an email template name is equal to its file name.

#### EntityBundle
* Refactored JS `EntityStructureDataProvider` [[?]](https://github.com/oroinc/platform/tree/master/src/Oro/Bundle/EntityBundle/Resources/public/js/app/services/entity-structure-data-provider.js) (see [documentation](https://doc.oroinc.com/master/bundles/platform/EntityBundle/entity-structure-data-provider/))
  - method `getPropertyPathByPath` renamed to `getRelativePropertyPathByPath`
  - method `getPathByPropertyPath` renamed to `getPathByRelativePropertyPath`
  - method `getPathByPropertyPathSafely` renamed to `getPathByRelativePropertyPathSafely`
* Decomposed `\Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRenderer` into separate processors and factories. Moved the TWIG sandbox configuration out of its responsibility.

#### FormBundle
* Refactored `ExpressionEditorView` [[?]](https://github.com/oroinc/platform/blob/master/src/Oro/Bundle/FormBundle/Resources/public/js/app/views/expression-editor-view.js) (see [documentation](https://doc.oroinc.com/master/bundles/platform/FormBundle/expression-editor/#expressioneditorview))
* Refactored `ExpressionEditorUtil` [[?]](https://github.com/oroinc/platform/tree/master/src/Oro/Bundle/FormBundle/Resources/public/js/expression-editor-util.js) (see [documentation](https://doc.oroinc.com/master/bundles/platform/FormBundle/expression-editor/#expressioneditorutil))
  * made it descendant of `BaseClass`
  * changed options format

#### LocaleBundle
* Updated `\Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider::setCurrentLocalization` to make it additionally switch localization, language, locale in `\Locale`, `\Gedmo\Translatable\TranslatableListener` and Symfony translator.

#### QueryDesignerBundle
* JS `*-filter-initialized` modules, defined over `init_module` option in filter configuration, now expected to export a sync callback function

### Datagrids
* All datagrids used in storefront application were moved from `<BundleName>/Resources/config/oro/datagrids.yml` to layouts folder of the `default` theme `<BundleName>/Resources/views/layouts/default/config/datagrids.yml`.
This means that storefront datagrids can now differ between themes. Any storefront grid customization should also be moved to the theme folder.

#### SearchBundle
* Added `\Oro\Bundle\SearchBundle\Event\BeforeIndexEntitiesEvent` that is dispatched in `\Oro\Bundle\SearchBundle\EventListener\IndexListener` in postFlush method after changing or updating indexing entities.

#### DataGridBundle
* Added postponed delete entities logic to `\Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler`.

#### UIBundle
* Removed `$offset-*` scss variables
* Added `spacing` scss function.
`$offset-x\$offset-y` => `spacing('base')`
`$offset-x-m\$offset-y-m` => `spacing('sm')`
`$offset-x-s\$offset-y-s` => `spacing('xs')`

### Removed

#### EmailBundle
* Removed `\Oro\Bundle\EmailBundle\Manager\EmailTemplateManager`, use instead `\Oro\Bundle\EmailBundle\Sender\EmailTemplateSender`.[[f]](#upgrade-toolkit-coverage)
* Removed `\Oro\Bundle\EmailBundle\Tools\EmailTemplateSerializer`, use instead `\Oro\Bundle\EmailBundle\Sender\EmailTemplateSender`.[[f]](#upgrade-toolkit-coverage)
* Removed `\Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider` use instead `\Oro\Bundle\EmailBundle\Provider\EmailTemplateProvider` and `\Oro\Bundle\EmailBundle\Provider\TranslatedEmailTemplateProvider`.[[f]](#upgrade-toolkit-coverage)
* Removed `\Oro\Bundle\EmailBundle\Provider\\Oro\Bundle\EmailBundle\Provider\EmailTemplateContentProvider`, use instead `\Oro\Bundle\EmailBundle\Provider\RenderedEmailTemplateProvider`.[[f]](#upgrade-toolkit-coverage)

#### NotificationBundle
* Removed `\Oro\Bundle\NotificationBundle\Manager\EmailNotificationSender`, use instead `\Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager`.[[f]](#upgrade-toolkit-coverage)
* Removed unused `\Oro\Bundle\NotificationBundle\Model\EmailTemplate`, use instead `\Oro\Bundle\EmailBundle\Model\EmailTemplate`.[[f]](#upgrade-toolkit-coverage)

## 5.1.0 (2023-03-31)

[Show detailed list of changes](incompatibilities-5-1.md)

### Migration of Extended Entities

To remove the code generation in runtime, OroPlatform now uses extendable implementation of magic __get, __set and __call methods.

To migrate your entities, follow the steps below:[[f]](#upgrade-toolkit-coverage)

1. Add `ExtendEntityInterface` implementation for extendable entity.
2. Add the usage of `ExtendEntityTrait` for extendable entity.
3. Remove the extended entity as a layer, moving the base extend classes to the main extendable entity.

Before:

```php
/**
 * Extendable User entity.
 */
class User extends ExtendUser implements
    EmailOwnerInterface,
    EmailHolderInterface,
    FullNameInterface,
    AdvancedApiUserInterface
{
}
```

```php
<?php

namespace Oro\Bundle\UserBundle\Model;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * This class is required to make User entity extendable.
 *
 * @method setAuthStatus(AbstractEnumValue $enum)
 * @method AbstractEnumValue getAuthStatus()
 */
abstract class ExtendUser extends AbstractUser
{
    /**
     * Constructor
     *
     * The real implementation of this method is auto generated.
     *
     * IMPORTANT: If the derived class has own constructor it must call parent constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }
}
```

After:

```php
/**
 * Extendable User entity.
 * 
 * @method setAuthStatus(AbstractEnumValue $enum)
 * @method AbstractEnumValue getAuthStatus()
 */
class User extends AbstractUser implements
    EmailOwnerInterface,
    EmailHolderInterface,
    FullNameInterface,
    AdvancedApiUserInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;  // The implementation of the ExtendEntityInterface 
}
```

#### Accessing Extended Properties and Methods[[f]](#upgrade-toolkit-coverage)

* To access the properties and methods of all entities, you must use the PropertyAccess factory methods.
* Method PropertyAccess::createPropertyAccessor() - used to create base symfony property accessor with custom reflection extractor. 
* Method PropertyAccess::createPropertyAccessorWithDotSyntax() - should be used instead of Oro\Component\PropertyAccess\PropertyAccessor.

Before:

```php
   protected function getPropertyAccessor(): PropertyAccessorInterface
   {
       if (!$this->propertyAccessor) {
           $this->propertyAccessor = new PropertyAccessor();
       }
       return $this->propertyAccessor;
   }
```

After:

```php
   protected function getPropertyAccessor(): PropertyAccessorInterface
   {
       if (!$this->propertyAccessor) {
           $this->propertyAccessor = PropertyAccess::createPropertyAccessor(); 
           // or PropertyAccess::createPropertyAccessorWithDotSyntax()
       }
       return $this->propertyAccessor;
   }
```

OR:

```yaml
services:
  acme_test.factory.test_factory:
    class: Acme\Bundle\TestBundle\Factory\TestFactory
    arguments:
      - '@property_accessor' // or '@oro_entity_extend.accessor.property_accessor_with_dot_array_syntax' instead of Oro PropertyAccessor
```

#### Extended Entity Helper

* For extended entities, we should use the helper method to check if extended property or method exists.
* Oro\Bundle\EntityExtendBundle\EntityPropertyInfo helper methods must be used instead of property_exists() or method_exists() native methods for extended entities.[[f]](#upgrade-toolkit-coverage)

**Important:** : property_exists() or method_exists() native methods are not working with extended entities.

Before:

```php
    if(property_exists($extendedEntity, 'extendPropertyName')) {
    }

    if(method_exists($extendedEntity, 'getExtendMethodName')) {
    }
```

After:

```php
    if(EntityPropertyInfo::propertyExists($extendedEntity, 'extendPropertyName')) {
    }

    if(EntityPropertyInfo::methodExists($extendedEntity, 'getExtendMethodName')) {
    }
```

#### Reflection Usage With Extended Entities[[f]](#upgrade-toolkit-coverage)

* For extended entities, use Oro\Bundle\EntityExtendBundle\EntityReflectionClass instead of \ReflectionClass;
* For extended properties, use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\ReflectionVirtualProperty
* For extended methods, use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\VirtualReflectionMethod

Before:

```php
   $reflectionClass = new ReflectionClass($className);
   $reflectionProperty = new ReflectionProperty($className, 'extendProperty')
   $reflectionProperty = new ReflectionMethod($className, 'getExtendPropertyMethod')
```

After:

```php
    $reflectionClass = new EntityReflectionClass($entity);
    $reflectionProperty = ReflectionVirtualProperty::create($extendPropertyName);
    $reflectionMethod = VirtualReflectionMethod::create($objectOrClass, $extendedMethodName);
```

#### Entity Generators

Instead of entity generators, implement ``Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldExtensionInterface``, that allows extending magic methods behavior in runtime.

#### Clone Extend Entity

To copy all the values of the extend entity (together with the virtual fields), call the
``ExtendEntityTrait->cloneExtendEntityStorage()`` method

#### Activity

Entities need to be extended manually, before ActivityInterface and ExtendActivity (trait) are added at the code
generation stage in the new implementation. 

Before:

```php
class Call extends ExtendCall implements DatesAwareInterface
{
}
```

After:

```php
class Call implements DatesAwareInterface, ActivityInterface, ExtendEntityInterface
{
use ExtendActivity;
use ExtendEntityTrait;
}
```

### Added

#### AttachmentBundle
* Added `Oro\Bundle\AttachmentBundle\Provider\OriginalFileNameProvider` filename provider that
  uses a sanitized original filename for files if `attachment_original_filenames` feature is enabled.
* Added `Oro\Bundle\AttachmentBundle\Entity\File::$externalUrl` property to store external file URL.
* Added `Oro\Bundle\AttachmentBundle\Provider\ExternalUrlProvider` (`oro_attachment.provider.external_url_provider`) that
  returns `Oro\Bundle\AttachmentBundle\Entity\File::$externalUrl` for a file, a resized or a filtered image URL.
* Added `oro_attachment.provider.external_url_provider` to the decorators chain of the file url providers
  `Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface` (`oro_attachment.provider.file_url`).
* Added `Oro\Bundle\AttachmentBundle\Model\ExternalFile` model as a descendant of `\SplFileInfo` that represents
  an externally stored file.
* Added `Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory` that creates `Oro\Bundle\AttachmentBundle\Model\ExternalFile`
  from a URL or `Oro\Bundle\AttachmentBundle\Entity\File` entity.
* Added `isExternalFile` form option to `Oro\Bundle\AttachmentBundle\Form\Type\FileType` that enables the external file URL
  input instead of the upload input.
* Added `Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormTypeProvider` that provides form type and common form options
  for extend fields.
* Added `Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsProvider` (`oro_entity_extend.provider.extend_field_form_options`)
  that collects extend field form options from the underlying providers
  of `Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsProviderInterface` interface.
* Added service container tag `oro_entity_extend.form_options_provider` for extend field form options providers to be used
  in `oro_entity_extend.provider.extend_field_form_options`.
* Added `Oro\Bundle\AttachmentBundle\Provider\ExtendFieldFileFormOptionsProvider` that provides form options for
  `file`, `image`, `multiFile`, `multiImage` types of extend fields.
* Added `Oro\Bundle\FormBundle\Validator\Constraints\RegExpSyntax` validation constraint for checking regular expression
  syntax.
* Added `Oro\Bundle\AttachmentBundle\ImportExport\FileManipulator` that uploads a file or clones it from the existing
  one during import.
  
#### DigitalAssetBundle
* Added `Oro\Bundle\DigitalAssetBundle\Provider\ExtendFieldFileDamFormOptionsProvider` that manages `dam_widget_enabled`
  form option based on `use_dam`, `is_stored_externally` entity field config values for `file`, `image`,
  `multiFile`, `multiImage` types of extend fields.
  
#### ImportExportBundle
* Added `markAsSkipped` and `isFieldSkipped` method to `\Oro\Bundle\ImportExportBundle\Event\DenormalizeEntityEvent`
  to mark certain field as skipped during denormalization process to avoid possible type conflicts.
  
#### LocaleBundle
* Added entity name provider for `Locale` entity
* Added `oro:localization:localized-fallback-values:cleanup-unused` command that finds and deletes orphaned
  `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue` entities that could appear due to disabled `orphanRemoval` option.
* Added `cloneLocalizedFallbackValueAssociations()` method that is generated automatically and should be used in
  `__clone()` for entities with localized fallback value relations to ensure correct cloning of localized fallback value
  collections.
*  Added `\Oro\Bundle\LocaleBundle\Cache\Normalizer\LocalizedFallbackValueCollectionNormalizer` and `\Oro\Bundle\LocaleBundle\Cache\Normalizer\LocalizedFallbackValueNormalizer` for using when caching complex structures

#### MigrationBundle
* For better data consistency and more valid testing scenarios, data fixtures are now validated during ORM demo data load, and Behat fixtures load.

#### SearchBundle
* Search query aggregations now can have parameters added via fourth parameter of the `addAggregate` method
* Added possibility to set maximum number of results for the count search aggregation

#### TestFrameworkBundle
* Added ``behat_test`` environment to run tests that depend on mocks in a separate environment.
* Marked all the tests that depend on behat_test environment with the ``@behat-test-env`` tag.
* Replaced message queue Behat isolators with the JobStatusSubscriber that checks the jobs table and does not depend on application changes
* Added the `--do-not-run-consumer` argument to the bin/behat command to run application tests in the production-like environment where the supervisord or systemd runs the consumer.
* Added maintenance mode isolators to toggle the maintenance mode when the database is backed up and restored so the message consumer does not produce errors during these operations.
* Updated some behat tests and contexts to work with the new isolators.

#### UIBundle
* Added `renderCollapsibleWysiwygContentPreview` and `renderWysiwygContentPreview` TWIG macros to UIBundle for
  rendering WYSIWYG content in backoffice.

* Added `oroui/js/app/modules/swipeable-module` instead of `swipeableView` to
  dispatch Custom Swipe Events to a document.
  The provided Swipe Events are:
  - The `swipestart` event is fired when one or more touch points are placed on the touch surface;
  - The `swipemove` event is fired when one or more touch points are moved along the touch surface
    with the detail option that includes the `x` and `y` coordinates of the pointer;
  - The `swipeend` event fires when one or more touch points are removed from the touch surface
    with the detail option that includes the `x` and `y` coordinates and `direction` of the pointer;
  - The `swipeleft` and `swiperight` events are fired when one or more touch points are moved along the touch surface
    with the detail option that includes the `x` and `y` coordinates of the pointer.
    It is fired only if the elapsed time between the start and end events is less than or equal to `maxAllowedTime`;

#### LayoutBundle
* Added `\Oro\Component\Layout\Extension\Theme\Model\ThemeManager::getThemesHierarchy` to easily get the theme hierarchy for the specified theme.
* Added the ability to specify response status code via layout context option `response_status_code`.

#### Layout Component
* Added `\Oro\Component\Layout\ContextInterface` and `\Oro\Component\Layout\LayoutContextStack` to `\Oro\Component\Layout\Layout` so now it is aware of own context and can push/pop the current context in the layout context stack.

#### NavigationBundle
* Added `\Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface::isSynthetic` to distinguish menu updates applied to the system menu items even if they do not exist anymore.
* Added "synthetic" field to `\Oro\Bundle\NavigationBundle\Entity\MenuUpdate`.
* Added `\Oro\Bundle\NavigationBundle\Event\MenuUpdatesApplyAfterEvent` that is dispatched in `\Oro\Bundle\NavigationBundle\Menu\MenuUpdateBuilder` after all menu updates are applied.
* Added `menu` form option as required for `\Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType`.
* Added `\Oro\Bundle\NavigationBundle\Manager\MenuUpdateDisplayManager`, `\Oro\Bundle\NavigationBundle\Manager\MenuUpdateMoveManager`.
* Added `\Oro\Bundle\NavigationBundle\Menu\DividerBuilder` that adds "divider" class to menu items.
* Added `\Oro\Bundle\NavigationBundle\Menu\HideEmptyItemsBuilder` that recursively hides menu items that do not have any children.
* Added `\Oro\Bundle\NavigationBundle\Menu\LostItemsBuilder` that removes menu items added by non-custom menu updates when target system menu item does not exist anymore.
* Added `\Oro\Bundle\NavigationBundle\Menu\OrphanItemsBuilder` that moves orphaned menu items to the menu root.
* Added `\Oro\Bundle\NavigationBundle\MenuUpdate\Applier\MenuUpdateApplier` that applies a menu update to menu item.
* Added `\Oro\Bundle\NavigationBundle\MenuUpdate\Factory\MenuUpdateFactory`.
* Added `\Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\MenuUpdateToMenuItemPropagatorInterface`, `\Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\BasicPropagator`, `\Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\ExtrasPropagator` and `\Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\CompositePropagator` that propagate the menu update data to the target menu item.
* Added `\Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils::createRecursiveIterator`, `\Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils::flattenMenuItem` for more convenient access to the menu item.

### Changed

* Updated from `slick-carousel: 1.7.1` to fork `@oroinc/slick-carousel: 1.7.1-oro1` with patched internal `postSlide` method
* Updated path of styles from `~slick-carousel/slick/slick.scss` to `~@oroinc/slick-carousel/slick/slick.scss`
  and path of js from `slick-carousel/slick/slick` to `@oroinc/slick-carousel/slick/slick`

#### TestUtils component
* Moved all ORM relates mocks and test cases to `Testing` component.
  Old namespace for these classes was `Oro\Component\TestUtils\ORM`.
  New namespace is `Oro\Component\Testing\Unit\ORM`.[[f]](#upgrade-toolkit-coverage)

#### ApiBundle
* The parameter `throwException` was removed from the method `convertToEntityType`
  of `Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil`. Use the `tryConvertToEntityType` method
  when an entity type might not exist.[[f]](#upgrade-toolkit-coverage)
* The parameter `throwException` was removed from the method `convertToEntityClass`
  of `Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil`. Use the `tryConvertToEntityClass` method
  when an entity class might not exist.[[f]](#upgrade-toolkit-coverage)
  
#### AssetBundle
* Changed configuration option `disable_babel` (`true` by default) to `with_babel` (`false` by default).

#### AttachmentBundle
* Changed `Oro\Bundle\AttachmentBundle\Entity\File::$file` property type to `?\SplFileInfo`
  to allow `Oro\Bundle\AttachmentBundle\Model\ExternalFile`. Methods `setFile` and `getFile` are changed correspondingly.[[p]](#upgrade-toolkit-coverage)
* Changed `Oro\Bundle\AttachmentBundle\Manager\FileManager::getFileFromFileEntity` return type to `?\SplFileInfo`
  to comply with `Oro\Bundle\AttachmentBundle\Entity\File::$file` property type.[[f]](#upgrade-toolkit-coverage)
* Changed `Oro\Bundle\AttachmentBundle\ImportExport\FileImportStrategyHelper::getFieldLabel` visibility to public,
  so it can be used for getting human-readable field names during import.[[f]](#upgrade-toolkit-coverage)
* Changed `Oro\Bundle\AttachmentBundle\ImportExport\EventListener\FileStrategyEventListener` constructor, so it expects
  `Oro\Bundle\AttachmentBundle\ImportExport\FileManipulator $fileManipulator`
  instead of `$fileManager`, also the `$authorizationChecker` argument is removed.

### CacheBundle

* The `oro.cache.abstract` abstract service is removed, use `oro.data.cache` instead, with the `cache.pool` tag 
and the namespace in a tag attribute.[[f]](#upgrade-toolkit-coverage)

  Before: 
  ```yaml
  services:
      oro_catalog.layout.data_provider.category.cache:
          parent: oro.cache.abstract
          public: false
          calls:
              - [ setNamespace, [ 'oro_catalog_category' ] ]
  ```
  After:
  ```yaml
  services:
      oro_catalog.layout.data_provider.category.cache:
          parent: oro.data.cache
          public: false
          tags:
              - { name: 'cache.pool', namespace: 'oro_catalog_category' }
  ```

#### DataGridBundle
* The `iconHideText` option for `action-launcher` and `dropdown-select-choice-launcher` views was removed, use the `launcherMode` option instead.
  The `launcherMode` option can have three different values:
    - `icon-text` - shows datagrid actions with icons and text labels;
    - `icon-only` - shows datagrid actions as icons;
    - `text-only`- shows datagrid actions as text labels;
    
#### DigitalAssetBundle
* Changed `Oro\Bundle\DigitalAssetBundle\ImportExport\EventListener\DigitalAssetAwareFileStrategyEventListener` constructor,
  so it expects `Oro\Bundle\AttachmentBundle\ImportExport\FileImportStrategyHelper $fileImportStrategyHelper`
  instead of `$doctrineHelper`.

#### EntityExtendBundle
* Changed `Oro\Bundle\EntityExtendBundle\Form\Guesser\ExtendFieldTypeGuesser` constructor, so it expects
  `Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormTypeProvider $extendFieldFormTypeProvider` and
  `Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsProviderInterface $extendFieldFormOptionsProvider`
  instead of `$enumConfigProvider`.
* Changed `Oro\Bundle\EntityExtendBundle\Form\Guesser\ExtendFieldTypeGuesser` so it gets the form type from
  `Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormTypeProvider` and the form options from
  `Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsProvider` now.

#### OrganizationBundle
* Changed the `cascade` Doctrine option of the `\Oro\Bundle\OrganizationBundle\Entity\Ownership\BusinessUnitAwareTrait::$owner`
  association: it is not cascade-persisted anymore.

#### PlatformBundle
* As the 'composer/composer' package is no longer used, the `Oro\Bundle\PlatformBundle\Provider\PackageProvider` class based services now provide the packages info in pure arrays instead of the array of the `Composer\Package\PackageInterface` interface based objects. The returned array structure is as follows: `['package_name' => ['pretty_version' => '1.0.0', 'license' => ['MIT']]]`..

#### ReminderBundle
* Reminder template messages are added as JS template macros under `reminderTemplates` namespace. Twig placeholder `oro_reminder_reminder_templates` no longer in use. 
  
#### SearchBundle
* Changed search engine configuration: `search_engine_dsn` parameter is used instead of `search_engine_name`, `search_engine_host`, `search_engine_port`, `search_engine_index_prefix`, `search_engine_username`, `search_engine_password`, `search_engine_ssl_verification`, `search_engine_ssl_cert`,  `search_engine_ssl_cert_password`, `search_engine_ssl_key`, `search_engine_ssl_key_password`.
* Entity title in the search index is no longer stored in the special field in the DB, now it is stored as a regular index text field called `system_entity_name`
* Entity title is no longer returned in the search results by default, now it has to be manually added to the select section of the query
* All entities presented in the search index now have proper entity name providers

#### RedisConfigBundle
* Added the bundle itself to `oro/platform` package. The bundle came from outer package `oro/redis-config` and it was rebuilt to utilize Symfony redis configuration components instead of once from 3rd party package `snc/redis-bundle`.

#### UIBundle
* `oroui/js/app/components/viewport-component` has been changed options from `viewport: {maxScreenType: 'tablet'}` or `viewport: {minScreenType: 'tablet'}` to `viewport: 'tablet'`
- As a result, you need to update your `html`:

  **view.twig**
    ```diff
      {% block _main_menu_container_widget %}
        {% set attr =  layout_attr_defaults(attr, {
          '~class': ' main-menu-outer',
          'data-page-component-module': 'oroui/js/app/components/viewport-component',
          'data-page-component-options': {
    -         viewport: {
    -             maxScreenType: 'tablet',
    -         },
              component: 'oroui/js/app/components/view-component',
              view: 'orocommercemenu/js/app/widgets/menu-traveling-widget'
          },
    ```
    ```diff
      {% block _main_menu_container_widget %}
        {% set attr =  layout_attr_defaults(attr, {
          '~class': ' main-menu-outer',
          'data-page-component-module': 'oroui/js/app/components/viewport-component',
          'data-page-component-options': {
    +         viewport: 'tablet',
              component: 'oroui/js/app/components/view-component',
              view: 'orocommercemenu/js/app/widgets/menu-traveling-widget'
          },
    ```
* `oroui/js/viewport-manager` has been update `isApplicable` method. From now on, the method accepts, as arguments, a string or an array of strings of media types.
- For example:
  + `viewportManager.isApplicable('tablet')`
  + `viewportManager.isApplicable('tablet', 'tablet-small')`
  + `viewportManager.isApplicable(['tablet', 'tablet-small'])`
- Added next public methods:
  + `getBreakpoints(context)`: returns object with all registered breakpoints from css property `--breakpoints`. It is possible to pass the `context` of the document as an argument.
  + `getMediaType(mediaType)`: returns `MediaQueryList` by `mediaType` argument.
- Removed next public methods:
  + `getViewport`
  + `getScreenType`
  + `getAllowScreenTypes`

The widgets `collapse-widget`, `collapse-group-widget`, `rows-collapse-widget` were removed, use the `bootstrap-collapse` instead.
- As a result, you need to update your `html`:

  **view.twig**
    ```diff
    - {% set collapseView = {
    -   storageKey: 'unique storage key',
    -   uid: 'unique storage key id',
    -   animationSpeed: 0,
    -   closeClass: 'overflows',
    -   forcedState: false,
    -   checkOverflow: false,
    -   open: false,
    -   keepState: false
    - } %}
    - <div class="collapse-block" data-page-component-collapse="{{ collapseView|json_encode }}">
    -   <div class="control-label" data-collapse-container>
    -     Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit
    -   </div>
    -   <a href="#" class="control-label toggle-more" data-collapse-trigger>{{ 'Show more'|trans }}</a>
    -   <a href="#" class="control-label toggle-less" data-collapse-trigger>{{ 'Show less'|trans }}</a>
    - </div>
    + {% set collapseId = 'collapse-'|uniqid %}
    + <div class="collapse-block">
    +   <div id="{{ collapseId }}" class="collapse-overflow collapse no-transition"
    +        data-collapsed-text="{{ 'Show more'|trans }}"
    +        data-expanded-text="{{ 'Show less'|trans }}"
    +        data-check-overflow="true"
    +        data-toggle="false"
    +        data-state-id="{{ 'unique storage key id' }}"
    +   >Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit...</div>
    +   <a href="#"
    +      role="button"
    +      class="collapse-toggle"
    +      data-toggle="collapse"
    +      data-target="{{ '#' ~ collapseId }}"
    +      aria-expanded="false"
    +      aria-controls="{{ collapseId }}"><span data-text>{{ 'Show more'|trans }}</span></a>
    + </div>
    ```

#### NavigationBundle
* Changed the sorting mechanism in `\Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider`: menu items are sorted as a single list instead of separate - sorted and unsorted parts. 

#### MessageQueueBundle
* Every custom MQ topic needs a `topic` class now; see more in the [Message Queue Topics](https://doc.oroinc.com/backend/mq/message-queue-topics/) topic.[[f]](#upgrade-toolkit-coverage)

#### WorkflowBundle
* To unify the WorkflowBundle and ActionBundle syntax, the `pre_conditions` and `post_actions` options of the workflow process definition configuration were renamed to `preconditions` and `actions` respectively.


### Removed

#### CronBundle
* `Oro\Bundle\CronBundle\Command\CronCommandInterface` has been removed.
  Use `Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface`
  and `Oro\Bundle\CronBundle\Command\CronCommandActivationInterface` instead.[[f]](#upgrade-toolkit-coverage)

#### DataGridBundle
* The deprecated `options / skip_acl_check` datagrid option was removed. Use the `source / skip_acl_apply` option instead.[[f]](#upgrade-toolkit-coverage)
* The deprecated `source / acl_resource` datagrid option was removed. Use the `acl_resource` option instead.[[f]](#upgrade-toolkit-coverage)

#### EntityBundle
* JS util `EntityFieldsUtil` was removed, use `EntityStructureDataProvider` instead.

#### EntityConfigBundle
* Removed `renderWysiwygContentPreview` TWIG macro from EntityConfigBundle, use `renderWysiwygContentPreview` or
  `renderCollapsibleWysiwygContentPreview` from UIBundle instead.
  
#### EntityExtendBundle
* Entity configuration option `search.title_field` has been removed
  
#### FilterBundle
* The `day-value-helper` was removed, use `date-value-helper` instead.

#### FormBundle
* `Oro\Bundle\FormBundle\Model\UpdateHandler` has been removed. Use `Oro\Bundle\FormBundle\Model\UpdateHandlerFacade` instead.[[f]](#upgrade-toolkit-coverage)

#### ImportExportBundle
* Removed `\Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent::setResultField`, use
  `\Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent::setResultFieldValue` instead.[[f]](#upgrade-toolkit-coverage)
  
#### InstallerBundle
* Removed `Oro\Bundle\InstallerBundle\EventListener\AssetsInstallCommandListener`, use JS packages from NPM

#### SearchBundle
* `title_fields` field from `search.yml` field has been removed[[f]](#upgrade-toolkit-coverage)

#### UIBundle
* CSSVariable parser `oroui/js/css-variables-manager` has been removed.
* CSSVariable module `oroui/js/app/modules/css-variable-module` has been removed.
* Removed `oroui/js/app/views/swipeable-view`, use `oroui/js/app/modules/swipeable-module` instead.
* `oroui/js/app/views/input-widget/checkbox` was removed; use pure CSS checkbox customization instead.
* The deprecated `tooltips` translation domain was removed. All translation from this domain were moved to the `messages` domain.
* The `modalHandler` method for `error` helper was removed, use `showError` method instead.
* The deprecated method `tools.loadModuleAndReplace()` from `'oroui/js/tools'` module, use `loadModules.fromObjectProp` from `'oroui/js/app/services/load-modules'` instead.
* `vertical_container` layout block type has been removed, as redundant. Use conventional `container` layout block type instead, with additions custom CSS class that implements required alignment.


#### LayoutBundle
* Removed `Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder`, use `Oro\Component\Layout\LayoutContextStack` instead.[[f]](#upgrade-toolkit-coverage)

#### NavigationBundle
* Removed `Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder`, added `\Oro\Bundle\NavigationBundle\Menu\MenuUpdateBuilder` instead.[[f]](#upgrade-toolkit-coverage)
* Removed `\Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface::getExtras`, `\Oro\Bundle\NavigationBundle\Entity\MenuUpdate::getExtras`, its purpose is moved to `\Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\ExtrasPropagator`.
* Removed `\Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager::moveMenuItem`, `\Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager::moveMenuItems`. Use corresponding methods from `\Oro\Bundle\NavigationBundle\Manager\MenuUpdateMoveManager` instead.
* Removed `\Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager::showMenuItem`, `\Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager::hideMenuItems`. Use corresponding methods from `\Oro\Bundle\NavigationBundle\Manager\MenuUpdateDisplayManager` instead.
* Removed `\Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils::updateMenuItem`, use `\Oro\Bundle\NavigationBundle\MenuUpdate\Applier\MenuUpdateApplier` instead.


## 5.0.0 (2022-01-26)
[Show detailed list of changes](incompatibilities-5-0.md)

### Added

* Added support for Right To Left UI design; see more in the [Right to Left UI Support](https://doc.oroinc.com/frontend/rtl-support/) topic. 

#### ApiBundle
* From now the `event` tag attribute for `customize_form_data` action API processor services is mandatory.
  This was made to prevent potential logical errors.
* `pre_flush_data`, `post_flush_data` and `post_save_data` events were added to the `customize_form_data` action.
  The `pre_flush_data` and `post_flush_data` events are dispatched together with the `flush()` method of
  the entity manager in the same database transaction.
  The `post_save_data` event is dispatched after the database transaction is committed.
  API processors for these events can be used to customize database update logic. 

#### AssetsBundle
* New assets versions strategy `Oro\Bundle\AssetBundle\VersionStrategy\BuildVersionStrategy` was added. It uses the `public/build/build_version.txt` application file's content as an assets version.

#### AttachmentBundle
* Added `oro_attachment.webp_strategy` configuration node to the bundle config to control whether to convert images to WebP format.
* Added `Oro\Bundle\AttachmentBundle\Manager\WebpAwareImageResizeManager` that additionally converts image to WebP format if needed.
* Added `Oro\Bundle\AttachmentBundle\Provider\FilterRuntimeConfigProviderInterface` and `Oro\Bundle\AttachmentBundle\Provider\FilterRuntimeConfigDefaultProvider`
  to provide LiipImagine filter runtime configuration for using in `Oro\Bundle\AttachmentBundle\Provider\ResizedImageProvider`.
* Added `Oro\Bundle\AttachmentBundle\Provider\WebpAwareFilterRuntimeConfigProvider` to provide LiipImagine filter runtime configuration
  for WebP format.
* Added `Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProviderInterface` and `Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProvider`
  to generate URL for static images.
* Added `Oro\Bundle\AttachmentBundle\Imagine\Provider\WebpAwareImagineUrlProvider` to generate URLs for static images in WebP format.
* Added `Oro\Bundle\AttachmentBundle\Provider\WebpAwareFileNameProvider` to generate filename taking into account current WebP strategy.
* Added `Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface` and `Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProvider`
  to provider image sources to be used in <picture> tag.

#### BatchBundle
* Added \Oro\Bundle\BatchBundle\Step\CumulativeStepExecutor and \Oro\Bundle\BatchBundle\Step\CumulativeItemStep with writer call for empty items.

#### DataGridBundle
* Added a unified way to disable columns, sorters, actions, and mass actions

#### EntityBundle
* Added `\Oro\Bundle\EntityBundle\ORM\DoctrineHelper::getManager` to get manager by name.

### FilterBundle
* Added new filter configuration variable `order` behavior according to [the documentation](https://doc.oroinc.com/master/bundles/platform/FilterBundle/grid-extension/)

#### ImportExportBundle
* Added `oro_importexport.strategy.configurable_import_strategy_helper` with performance improvements to replace `oro_importexport.strategy.import.helper` in strategries.
* Added `\Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent` to handle validation errors formatting cases.
* Added `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableImportStrategyHelper` to improve import performance.
* Added `\Oro\Bundle\ImportExportBundle\Writer\CumulativeWriter` to improve import performance.
* Added `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy::isEntityFieldFallbackValue` to support `\Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue` import.
* Added `\Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper::getEntityPropertiesByClassName` to support `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableImportStrategyHelper` import.
* Added `\Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper::verifyClass` to support `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableImportStrategyHelper` import.

#### LocaleBundle
* Added `\Oro\Bundle\LocaleBundle\EventListener\StrategyValidationEventListener` to format `LocalizedFallbackValue` error keys.

#### MessageQueueBundle
* Added `message_queue` connection.
* Added metadata cache for `message_queue` entity manager.
* Added `\Oro\Bundle\MessageQueueBundle\Platform\{OptionalListenerDriver,OptionalListenerDriverFactory,OptionalListenerExtension}` to bypass optional listeners from CLI to MQ.

#### MessageQueue Component
* Added `\Oro\Component\MessageQueue\Consumption\Extension\LimitGarbageCollectionExtension` to limit consumer by GC runs.
* Added `\Oro\Component\MessageQueue\Consumption\Extension\LimitObjectExtension` to limit consumer by objects in runtime.
* Added `Oro\Component\MessageQueue\Topic\TopicInterface` to declare topic name, description, message default priority 
  and message body structure for the MQ topics.
* Added `oro_message_queue.topic` tag for declaring MQ topic in a service container.
* Added `Oro\Component\MessageQueue\Topic\TopicRegistry` that contains MQ topics declared as services with tag `oro_message_queue.topic`.
* Added `Oro\Component\MessageQueue\Client\MessageBodyResolverInterface` and `Oro\Component\MessageQueue\Client\MessageBodyResolver`
  to validate the topic message body structure.
* Added `Oro\Component\MessageQueue\Client\ConsumptionExtension\MessageBodyResolverExtension` MQ extension 
  that resolves message body before it is passed to MQ processor.
  
#### PlatformBundle
* Added \Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\DoctrineTagMethodPass to handle unsupported method definitions for Doctrine events.

#### TestFrameworkBundle
* Optional listeners (except search listeners) disabled in functional tests by default. Use `$this->getOptionalListenerManager()->enableListener('oro_workflow.listener.event_trigger_collector');` to enable listeners in tests.
* Added additional hook for client cleanup - `@beforeResetClient`, use it instead of `@after` for full tests isolation.


### Changed

* All application distributions' `config.xml` files were changed to point `Oro\Bundle\AssetBundle\VersionStrategy\BuildVersionStrategy` assets version strategy to be used.

#### ApiBundle
* Changed connection from `batch` to `message_queue`

#### AttachmentBundle
* Changed `Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface::getResizedImageUrl()`,
  `Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface::getFilteredImageUrl()`,
  `Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface::getPathForResizedImage()`,
  `Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface::getPathForFilteredImage()`:
  added `$format` argument to specify the resized image format.
* Changed `Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface`:
  added `getFilteredImageName()`, `getResizedImageName()` that should be used for getting names 
  for filtered and resized images correspondingly.
* Changed `Oro\Bundle\AttachmentBundle\Provider\ResizedImageProvider::getFilteredImage()`,
  `Oro\Bundle\AttachmentBundle\Provider\ResizedImageProvider::getResizedImage()`,
  `Oro\Bundle\AttachmentBundle\Manager\ImageResizeManager::resize()`,
  `Oro\Bundle\AttachmentBundle\Manager\ImageResizeManager::applyFilter()`:
  added `$format` argument to specify the resized image format.

#### Config component
* Added sorting by depth to `Oro\Component\Config\Loader\FolderContentCumulativeLoader::getDirectoryContents()`
  to ensure that result is not affected by an operating system.
  
#### DataGridBundle
* Changed filter configuration variable from `enabled` to `renderable`[[f]](#upgrade-toolkit-coverage)

#### EmbeddedFormBundle
* In `Oro\Bundle\EmbeddedFormBundle\Controller\EmbeddedFormController::defaultDataAction`
  (`oro_embedded_form_default_data` route)
  action the request method was changed to GET.
  
#### EntityBundle

* Parent class for repositories as a services was changed to `Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository`.

#### FormBundle
 * validator `oroform/js/validator/url` is excluded from JS-build for blank theme, due to it is too heavy and not in use on the front. It can be included again in custom theme if needed.
 
#### ImportExportBundle
* Changed step class and writer service for `entity_import_from_csv` to improve import performance.
* Changed `oro_importexport.strategy.add` and all strategies `oro_importexport.strategy.import.helper` implementation to `oro_importexport.strategy.configurable_import_strategy_helper`
* Changed `\Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ScalarFieldDenormalizer` to handle advanced boolean fields cases - yes/no, true/false, 1/0.
* Changed `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy::process` to process validation errors gracefully.
* Changed `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy::updateRelations` to avoid massive collection changes.
* Changed `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy::processValidationErrors` to improve validation errors processing.
* Changed `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy::getObjectValue` to support edge cases, like User#roles.

#### InstallBundle
* The composer's assets version set script`Oro\Bundle\InstallerBundle\Composer\ScriptHandler::setAssetsVersion` was changed to store time base hash value into `public/build/build_version.txt` application file.

#### LocaleBundle
* Changed `\Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy` for performance reasons, error keys logic moved to `\Oro\Bundle\LocaleBundle\EventListener\StrategyValidationEventListener`.

#### MessageQueueBundle
* Changed connection from `batch` to `message_queue`

#### MessageQueue Component
* Changed `\Oro\Component\MessageQueue\Transport\MessageInterface::getBody()`, `\Oro\Component\MessageQueue\Transport\MessageInterface::setBody()`
  signature - `$body` argument is `mixed` now, i.e. can be of any type returned by `json_decode()`.
* Moved JSON encoding of message body from client message producer to transport level `Oro\Component\MessageQueue\Transport\MessageProducerInterface` -
  to `Oro\Component\MessageQueue\Transport\Dbal\DbalMessageProducer`.
* Moved JSON decoding of message body to transport level `Oro\Component\MessageQueue\Transport\MessageConsumerInterface` - 
  to `Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer`.
* Added the validation of message body to `Oro\Component\MessageQueue\Client\MessageProducer` using `Oro\Component\MessageQueue\Client\MessageBodyResolverInterface`.

#### PlatformBundle
* Changed \Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass to apply default connection (instead of all) for Doctrine events when it's empty in a tag.

#### SearchBundle
* `oro_search.fulltext_index_manager` to use `doctrine.dbal.search_connection`
* `oro_search.event_listener.orm.fulltext_index_listener` to use `doctrine.dbal.search_connection`

#### TestFrameworkBundle
* Public methods `newBrowserTabIsOpened` and `newBrowserTabIsOpenedAndISwitchToIt` are moved from `Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext` to dedicated context `Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\BrowserTabContext`.

#### UIBundle
* `Mixins` and `SCSS Variables` related to `direction.scss` were removed. For implementing Right To Left UI design have a look in [Right to Left UI Support](https://doc.oroinc.com/frontend/rtl-support/).
* Style build process for back-office is the same as for layout's themes. All `inputs` files are imported into one `root.scss`, that is used as entry point for building styles. 
  As result, all SCSS variables available from global scope, no need to import them manually into local style from a bundle.
  All inputs are imported in following order `**/settings/**`, `**/variables/**` and rest of styles, that allows to modify variable's value before it is used. That is aimed to simplify customization.

#### ValidationBundle
 * validator `orovalidation/js/validator/url` is excluded from JS-build for blank theme, due to it is too heavy and not in use on the front. It can be included again in custom theme if needed.
  
#### @oroinc/webpack-config-builder
* Platform now requires updated version of `@oroinc/webpack-config-builder` package which is migrated to Webpack 5. See [Webpack migration guide](https://webpack.js.org/migrate/5/).

### Removed

* `assets_version` and `assets_version_strategy` container parameters were removed from all application distributions.
* Symfony's assets version strategy `framework.assets.version` and `framework.assets.version` keyed parameters were removed from `config.xml` file in all application distributions.

#### BatchBundle
* Removed `batch` connection, use `message_queue` connection instead.

#### ConfigBundle
* The DIC compiler pass `Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\ListenerExcludeConfigConnectionPass`
  was removed. It is unneeded since the `doctrine.exclude_listener_connections` DIC parameter is no longer in use.

#### CronBundle 
* Removed `Oro\Bundle\CronBundle\Async\Topics`, use getName() of corresponding topic class from `Oro\Bundle\CronBundle\Async\Topic` namespace instead.[[f]](#upgrade-toolkit-coverage)

#### DataAuditBundle
* Removed `Oro\Bundle\DataAuditBundle\Async\Topics`, use getName() of corresponding topic class from `Oro\Bundle\DataAuditBundle\Async\Topic` namespace instead.[[f]](#upgrade-toolkit-coverage)

#### EntityBundle
* The service `oro_entity.abstract_entity_manager` was removed.
* `Oro\Bundle\EntityBundle\Provider\EntityFieldProvider::getFields()` was removed,
  use `Oro\Bundle\EntityBundle\Provider\EntityFieldProvider::getEntityFields()` instead.[[f]](#upgrade-toolkit-coverage)
* `Oro\Bundle\EntityBundle\Helper\FieldHelper::getFields()` was removed,
  use `Oro\Bundle\EntityBundle\Helper\FieldHelper::getEntityFields()` instead.[[f]](#upgrade-toolkit-coverage)
* The service `oro_entity.repository.factory` was removed.
* The DIC compiler pass `Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityRepositoryCompilerPass` was removed.

#### EntityMergeBundle
* The service `oro_entity_merge.accessor.delegate` was removed. Use `oro_entity_merge.accessor` instead.[[f]](#upgrade-toolkit-coverage)
* The service `oro_entity_merge.strategy.delegate` was removed. Use `oro_entity_merge.strategy` instead.[[f]](#upgrade-toolkit-coverage)

#### EmailBundle 
* Removed `Oro\Bundle\EmailBundle\Async\Topics`, use getName() of corresponding topic class from `Oro\Bundle\EmailBundle\Async\Topic` namespace instead.[[f]](#upgrade-toolkit-coverage)

#### NotificationBundle 
* Removed `Oro\Bundle\NotificationBundle\Async\Topics`, use getName() of corresponding topic class from `Oro\Bundle\NotificationBundle\Async\Topic` namespace instead.[[f]](#upgrade-toolkit-coverage)

#### ImapBundle 
* Removed `Oro\Bundle\ImapBundle\Async\Topics`, use getName() of corresponding topic class from `Oro\Bundle\ImapBundle\Async\Topic` namespace instead.[[f]](#upgrade-toolkit-coverage)

#### MessageQueueBundle 
* Removed `Oro\Bundle\MessageQueueBundle\Async\Topics`, use getName() of corresponding topic class from `Oro\Bundle\MessageQueueBundle\Async\Topic` namespace instead.[[f]](#upgrade-toolkit-coverage)

* Removed `Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicDescriptionPass`, declare topic 
  via `Oro\Component\MessageQueue\Topic\TopicInterface` as a service with tag `oro_message_queue.topic` instead.
  
#### MessageQueue Component
* Removed `Oro\Component\MessageQueue\Job\Topics`, use getName() of corresponding topic class from `Oro\Component\MessageQueue\Job\Topic` namespace instead.[[f]](#upgrade-toolkit-coverage)

#### PlatformBundle
* `doctrine.exclude_listener_connections` parameter is no longer in use.

#### TranslationBundle 
* Removed `Oro\Bundle\TranslationBundle\Async\Topics`, use getName() of corresponding topic class from `Oro\Bundle\TranslationBundle\Async\Topic` namespace instead.[[f]](#upgrade-toolkit-coverage)

#### SearchBundle 
* Removed `Oro\Bundle\SearchBundle\Async\Topics`, use getName() of corresponding topic class from `Oro\Bundle\SearchBundle\Async\Topic` namespace instead.[[f]](#upgrade-toolkit-coverage)

* The DIC compiler pass `Oro\Bundle\SearchBundle\DependencyInjection\Compiler\ListenerExcludeSearchConnectionPass`
  was removed. It is unneeded since the `doctrine.exclude_listener_connections` DIC parameter is no longer in use.

#### UIBundle
* Remove reset style for ordered and unordered list in `Resources/public/blank/scss/reset.scss`

* The `oro_ui_content_provider_manager` global variable was removed from Twig.
  Use the `oro_get_content` Twig function instead.
  
* A separate styles build for third-party libraries with RTL support was removed for back-office themes. Now, RTL styles are build the same way as for Layout Themes. See [Right to Left UI Support](https://doc.oroinc.com/frontend/rtl-support/#configure-theme)
* `isIE11` and `isEDGE` methods are removed from `oroui/js/tools` module




## 4.2.10

### Changed

#### EmailBundle
* Added `getOrganizations()` and `getEmails()` methods to `Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface`.

## 4.2.4

### Added

#### DataGridBundle

* Added `orodatagrid/js/cell-links/builder` to provide behavior row as link. In customizations, it is not recommended to use selectors like `:first-child`, `:nth-child(n)` etc. Because it can affect this functionality. If necessary, wrap the cell content in a container.

#### EmailBundle
* Added `Oro\Bundle\EmailBundle\Mailer\Mailer` to send `Symfony\Component\Mime\Email` message.
* Added `Oro\Bundle\EmailBundle\Sender\EmailModelSender` to send `Oro\Bundle\EmailBundle\Form\Model\Email` model.
* Added `Oro\Bundle\EmailBundle\Mailer\Transport\Transport` decorator that dispatches `Oro\Bundle\EmailBundle\Event\BeforeMessageEvent`
  and adds extra logging for mailer transports;
* Added `Oro\Bundle\EmailBundle\Mailer\Transport\LazyTransports` decorator that defers transports instantiation until 
  method `send()`  is called.
* Added `Oro\Bundle\EmailBundle\Event\BeforeMessageEvent` event that is dispatched by `Oro\Bundle\EmailBundle\Mailer\Transport\Transport`
  to allow altering email message and envelope.
* Added `Oro\Bundle\EmailBundle\Mailer\Envelope\EmailOriginAwareEnvelope` that adds ability to specify `Oro\Bundle\EmailBundle\Entity\EmailOrigin`
  in the email message envelope.
* Added `Oro\Bundle\EmailBundle\Mailer\Transport\SystemConfigTransportFactory` for `oro://system-config` mailer transport.
* Added `Oro\Bundle\EmailBundle\Mailer\Transport\SystemConfigTransportRealDsnProvider` for resolving real DSN that lies behind
  `oro://system-config` DSN.
* Added `Oro\Bundle\EmailBundle\Mailer\Checker\ConnectionCheckers` that allows to check if the mailer transport connection 
  specified in the DSN is valid.
* Added `Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesExtractor`, `Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesInEmailModelHandler`,
  `Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesInSymfonyEmailHandler` for extracting and handling embedded images in
  `Oro\Bundle\EmailBundle\Form\Model\Email` and `Symfony\Component\Mime\Email` models.

#### ImapBundle
* Added `Oro\Bundle\ImapBundle\Mailer\Transport\UserEmailOriginTransportFactory` for `oro://user-email-origin` mailer transport.
* Added `Oro\Bundle\ImapBundle\Mailer\Transport\UserEmailOriginTransport` to send email messages using SMTP settings 
  taken from `Oro\Bundle\ImapBundle\Entity\UserEmailOrigin`
* Added `Oro\Bundle\ImapBundle\Validator\Constraints\SmtpConnectionConfiguration` and `Oro\Bundle\ImapBundle\Validator\SmtpConnectionConfigurationValidator`
  for validating SMTP settings taken from `Oro\Bundle\ImapBundle\Entity\UserEmailOrigin`.
* Added `Oro\Bundle\ImapBundle\EventListener\SetUserEmailOriginTransportListener` for adding `X-Transport` and 
  `X-User-Email-Origin-Id` headers to email message required to use `oro://user-email-origin` mailer transport.

#### LayoutBundle

* Provided way to create separate JS builds only with essential modules for landing pages, see article [How to Create Extra JS Build for a Landing Page](https://doc.oroinc.com/master/frontend/storefront/how-to/how-to-create-extra-js-build-for-landing-page/).
* Added configuration option for the list of enabled layout theme, see [How to Enabled the Theme](https://doc.oroinc.com/master/frontend/storefront/theming/#add-the-theme-to-enabled-themes-list).

#### LoggerBundle
* Added `Oro\Bundle\LoggerBundle\Monolog\ErrorLogNotificationHandlerWrapper` monolog handler wrapper to prevent error log
  notification from being sent when there are no recipients configured.

#### NotificationBundle
* Added `Oro\Bundle\NotificationBundle\Async\SendEmailNotificationProcessor` for processing MQ messages for sending 
  email notification messages in a message queue.
* Added `Oro\Bundle\NotificationBundle\Async\Topics::SEND_NOTIFICATION_EMAIL_TEMPLATE` MQ topic and 
  `Oro\Bundle\NotificationBundle\Async\SendEmailNotificationTemplateProcessor` processor for processing MQ messages 
  for sending templated email notification messages in a message queue.
* Added `Oro\Bundle\NotificationBundle\Mailer\MassNotificationsMailer` for sending mass notification email messages.

#### MessageQueue component
* Added `Oro\Component\MessageQueue\Client\ConsumptionExtension\MessageProcessorRouterExtension` for message routing
  instead of `Oro\Component\MessageQueue\Client\DelegateMessageProcessor`.
* Added `Oro\Component\MessageQueue\Client\NoopMessageProcessor` for messages which are not claimed by any message
  processor.
* Added `Oro\Component\MessageQueue\Client\Router\MessageRouter` and `Oro\Component\MessageQueue\Client\Router\Envelope`
  for handling messages routing into queues in `Oro\Component\MessageQueue\Client\MessageProducer`.
* Added `Oro\Component\MessageQueue\Client\MessageProcessorRegistry` service locator.
* Added `Oro\Component\MessageQueue\Consumption::getMessageProcessorName()`,
  `Oro\Component\MessageQueue\Consumption::setMessageProcessorName()`.
* Added `Oro\Component\MessageQueue\Log\ConsumerState`::getMessageProcessorName(),
  `Oro\Component\MessageQueue\Log\ConsumerState`::setMessageProcessorName().
* Added `Oro\Component\MessageQueue\Client\Meta\TopicDescriptionProvider`.
* Added `\Oro\Component\MessageQueue\Log\MessageProcessorClassProvider::getMessageProcessorClassByName()` for getting
  message processor class name by processor name.

#### MessageQueueBundle
* Added `client.noop_status` used for messages not claimed by any message processor.

### Changed

#### TranslationBundle
* Changed translation cache generation logic. Now all translation strings that contain HTML tags are sanitized by the HTMLPurifier before caching.
  To get the list of translation messages that were sanitized run oro:translation:rebuild-cache with --show-sanitization-errors option.

### EmailBundle
* EmailBundle uses Symfony Mailer instead of SwiftMailer from now on.
* Changed mailer configuration: `mailer_dsn` parameter is used instead of `mailer_transport`, `mailer_host`, `mailer_port`,
  `mailer_encryption`, `mailer_user`, `mailer_password`.

#### MessageQueue component
* Argument `processor-service` of `Oro\Component\MessageQueue\Consumption\ConsumeMessagesCommand` is optional now.

### Removed

* `installed` container parameter was removed from all application distributions. You can get the installation state by calling the `isInstalled` method of the `Oro\Bundle\DistributionBundle\Handler\ApplicationState` service.

#### EmailBundle
* Removed `Oro\Bundle\EmailBundle\Async\TemplateEmailMessageSender`.
* Removed `Oro\Bundle\EmailBundle\Event\SendEmailTransport`, use `X-Transport` email message header for configuring 
  mailer transport. See `Oro\Bundle\ImapBundle\EventListener\SetUserEmailOriginTransportListener`.
* Removed `Oro\Bundle\EmailBundle\Mailer\DirectMailer`, use `Oro\Bundle\EmailBundle\Mailer\Mailer` instead.[[f]](#upgrade-toolkit-coverage)
* Removed `Oro\Bundle\EmailBundle\Mailer\Processor`, use `Oro\Bundle\EmailBundle\Sender\EmailModelSender` instead.[[f]](#upgrade-toolkit-coverage)
* Removed unused `Oro\Bundle\EmailBundle\Util\MailerWrapper`.
* Removed `Oro\Bundle\EmailBundle\Model\DTO\EmailAddressDTO`, use `\Oro\Bundle\EmailBundle\Model\Recipient` instead.[[f]](#upgrade-toolkit-coverage)

#### ImapBundle
* Removed `Oro\Bundle\ImapBundle\EventListener\SendEmailTransportListener` in favor 
  of `Oro\Bundle\ImapBundle\EventListener\SetUserEmailOriginTransportListener`.[[f]](#upgrade-toolkit-coverage)

#### LoggerBundle
* Removed `Oro\Bundle\LoggerBundle\Mailer\NoRecipientPlugin` in favor of `Oro\Bundle\LoggerBundle\Monolog\ErrorLogNotificationHandlerWrapper`.[[f]](#upgrade-toolkit-coverage)
* Removed `Oro\Bundle\LoggerBundle\Mailer\MessageFactory` in favor of `Oro\Bundle\LoggerBundle\Monolog\EmailFactory\ErrorLogNotificationEmailFactory`.[[f]](#upgrade-toolkit-coverage)

#### NotificationBundle
* Removed `Oro\Bundle\NotificationBundle\Async\SendEmailMessageProcessor`. Use `Oro\Bundle\NotificationBundle\Async\SendEmailNotificationProcessor`
  and `Oro\Bundle\NotificationBundle\Async\SendEmailNotificationTemplateProcessor` instead.[[f]](#upgrade-toolkit-coverage)
* Removed `Oro\Bundle\NotificationBundle\Async\SendMassEmailMessageProcessor` in favor of `Oro\Bundle\NotificationBundle\Async\SendEmailNotificationProcessor`.[[f]](#upgrade-toolkit-coverage)
* Removed `Oro\Bundle\NotificationBundle\Mailer\MassEmailDirectMailer`. Use `Oro\Bundle\NotificationBundle\Mailer\MassNotificationsMailer` instead.[[f]](#upgrade-toolkit-coverage)
* Removed unused `\Oro\Bundle\NotificationBundle\Entity\SpoolItem` entity, `\Oro\Bundle\NotificationBundle\Entity\Repository\SpoolItemRepository`.
* Removed unused `Oro\Bundle\NotificationBundle\Provider\Mailer\DbSpool`. 

#### MessageQueue component
* Removed unused `Oro\Component\MessageQueue\Router`, `Oro\Component\MessageQueue\Router\RouteRecipientListProcessor`,
  `Oro\Component\MessageQueue\Router\Recipient`.
* Removed unused `Oro\Component\MessageQueue\Client\Config::getRouterMessageProcessorName()`,
  `Oro\Component\MessageQueue\Client\Config::getRouterQueueName()`.
* Removed `Oro\Component\MessageQueue\Client\ContainerAwareMessageProcessorRegistry`, use instead
  `Oro\Component\MessageQueue\Client\MessageProcessorRegistry` service locator.[[f]](#upgrade-toolkit-coverage)
* Removed `Oro\Component\MessageQueue\Client\DelegateMessageProcessor`, the extension
  `Oro\Component\MessageQueue\Client\ConsumptionExtension\MessageProcessorRouterExtension` is responsible for routing to
  message processor now.
* Removed `Oro\Component\MessageQueue\Consumption::getMessageProcessor()` and
  `Oro\Component\MessageQueue\Consumption::setMessageProcessor()`, use instead
  `Oro\Component\MessageQueue\Consumption::getMessageProcessorName()`,
  `Oro\Component\MessageQueue\Consumption::setMessageProcessorName()`.[[f]](#upgrade-toolkit-coverage)
* Removed `Oro\Component\MessageQueue\Log\ConsumerState`::getMessageProcessor() and
  `Oro\Component\MessageQueue\Log\ConsumerState`::setMessageProcessor(), use instead
  `Oro\Component\MessageQueue\Log\ConsumerState`::getMessageProcessorName(),
  `Oro\Component\MessageQueue\Log\ConsumerState`::setMessageProcessorName() .[[f]](#upgrade-toolkit-coverage)
* Removed `Oro\Component\MessageQueue\Client\Meta\TopicMeta::getDescription()`, use
  `Oro\Component\MessageQueue\Client\Meta\TopicDescriptionProvider::getTopicDescription()` instead.[[f]](#upgrade-toolkit-coverage)
* Removed `\Oro\Component\MessageQueue\Log\MessageProcessorClassProvider::getMessageProcessorClass()`, use
  `\Oro\Component\MessageQueue\Log\MessageProcessorClassProvider::getMessageProcessorClassByName()` instead.[[f]](#upgrade-toolkit-coverage)

#### MessageQueueBundle
* Removed unused `client.router_processor`, `client.router_destination` configuration options.
* Removed `Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildRouteRegistryPass`
* Removed `Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\ProcessorLocatorPass` and
  `oro_message_queue.processor_locator`, use instead
  `Oro\Component\MessageQueue\Client\ContainerAwareMessageProcessorRegistry`.
* Removed `processorName` attribute from `oro_message_queue.client.message_processor` tag, use service id instead.
* Removed ability to specify `processorName` in `Oro\Component\MessageQueue\Client\TopicSubscriberInterface::getSubscribedTopics()`,
  use service id instead.
* Removed `Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass`, use
  `Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicDescriptionPass` for specifying topic
  descriptions. `Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildTopicMetaRegistryPass` is used to
  collect topic metadata into `Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry` now.

#### UIBundle

Third party dependencies such as [Font Awesome](https://fontawesome.com/v4.7/) and [Bootstrap](https://getbootstrap.com/docs/4.6/getting-started/introduction/) where forked.
  - As a result, you need to update your `scss` and configuration files:

    **assets.yml**
   
      ```diff
      # ...
          inputs:
      -        - '~bootstrap/scss/nav'
      +        - '~@oroinc/bootstrap/scss/nav'
      ```
    **\*.scss**
      ```diff     
    
      - @import "~bootstrap/scss/variables";
      + @import "~@oroinc/bootstrap/scss/variables";
      ```

    **jsmodules.yml**
      ```diff
      # ...
      - bootstrap-alert$: bootstrap/js/dist/alert
      + bootstrap-alert$: '@oroinc/bootstrap/js/dist/alert'
      ```
      
      
## 4.2.2

### Changed

#### EntityExtendBundle
* The `force` option was added to the `oro:entity-extend:update-config` CLI command to avoid accidental execution of it.

#### LocaleBundle
* The unused service `oro_locale.repository.localization` was removed.

## 4.2.0 (2020-01-29)
[Show detailed list of changes](incompatibilities-4-2.md)

### Added

#### ApiBundle
* Implemented support of the `inherit_data` form option for the `nestedObject` data type. It allows to configure
  nested objects even if an entity does not have a setter method for it.
  
#### LayoutBundle
* Added `is_xml_http_request` option to the Layout context which lets you know if the current request is an ajax request.
* Added two new options `onLoadingCssClass` and `disableControls` to the `layout_subtree_update` block configuration.

#### MessageQueueBundle
* Added a possibility to filter messages before they are sent to the message queue.
  See [Filtering Messages in the Message Producer](https://doc.oroinc.com/backend/mq/filtering-messages/).
  
#### SecurityBundle
* Added `generate_uuid` action. The action generates UUID and puts the value to the specified attribute.

#### TranslationBundle
* Added migration query `Oro\Bundle\TranslationBundle\Migration\DeleteTranslationsByDomainAndKeyPrefixQuery` that can be used to remove unused translations by domain and key prefix.

#### WorkflowBundle
* Added migration query `Oro\Bundle\WorkflowBundle\Migration\RemoveWorkflowAwareEntitiesQuery` that can be used to remove instances of entity created from the specified workflow.
* Added method `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::transitUnconditionally()`. The method transits a workflow item without checking for preconditions and conditions.

### Changed

#### AttachmentBundle
* The service `oro_attachment.manager.media_cache_manager_registry` was renamed to `oro_attachment.media_cache_manager_registry`.[[f]](#upgrade-toolkit-coverage)
* The service `oro_attachment.provider.attachment_file_name_provider` was renamed to `oro_attachment.provider.file_name`.[[f]](#upgrade-toolkit-coverage)

#### DataGridBundle
* The maximum number of items can be deleted at once during mass delete process was decreased to 100.

#### EntityBundle
* The service `oro_entity.virtual_field_provider.chain` was renamed to `oro_entity.virtual_field_provider`.[[f]](#upgrade-toolkit-coverage)
* The service `oro_entity.virtual_relation_provider.chain` was renamed to `oro_entity.virtual_relation_provider`.[[f]](#upgrade-toolkit-coverage)

#### FormBundle
* Upgraded TinyMCE library to version 5.6.0, see [migration guide](https://www.tiny.cloud/blog/how-to-migrate-from-tinymce-4-to-tinymce-5/)
  * Removed the `bdesk_photo` plugin, use the standard `image` plugin and the toolbar button instead.
  * Major UX changes:
    * the default skin of editor 5.6.0
    * popups "add link" and "add image" are not oro-themed
    * fullscreen mode is actual full screen, without the page container limit like previously
    * changed UX for adding embedded image
  * Minor UX changes:
    * editor's width is 100% by default
    * status bar is turned on by default (to allow to resize the editor vertically)
    * the element path is mostly turned off by default. It is turned on only in places where the status bar was enabled before

#### NotificationBundle

* `Oro\Bundle\NotificationBundle\Entity\Event` and
  `Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\RegisterNotificationEventsCompilerPass` classes were deleted.

  To migrate custom notification events, delete all the usages of `Event` and `RegisterNotificationEventsCompilerPass` classes
  and register events with the YAML configuration according to [the documentation](http://doc.oroinc.com/master/backend/bundles/platform/NotificationBundle/notification-event/).
  
#### PlatformBundle
* The handling of `priority` attribute for `oro_platform.console.global_options_provider` DIC tag
  was changed to correspond Symfony recommendations.[[f]](#upgrade-toolkit-coverage)
  If you have services with this tag, change the sign of the priority value for them.
  E.g. `{ name: oro_platform.console.global_options_provider, priority: 100 }` should be changed to
  `{ name: oro_platform.console.global_options_provider, priority: -100 }`

#### QueryDesignerBundle
* The class `Oro\Bundle\QueryDesignerBundle\QueryDesigner\AbstractQueryConverter` was refactored to decrease its complexity.
  The state of all query converters was moved to "context" classes.
  The base context class is `Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryConverterContext`.
  If you have own query converters, update them according to new architecture.
* The class `Oro\Bundle\QueryDesignerBundle\QueryDesigner\FilterProcessor` was renamed to `Oro\Bundle\SegmentBundle\Query\FilterProcessor`.[[f]](#upgrade-toolkit-coverage)
* The service `oro_query_designer.query_designer.filter_processor` was renamed to `oro_segment.query.filter_processor`.[[f]](#upgrade-toolkit-coverage)

#### SecurityBundle
* The handling of `priority` attribute for `oro.security.filter.acl_privilege` DIC tag
  was changed to correspond Symfony recommendations.[[f]](#upgrade-toolkit-coverage)
  If you have services with this tag, change the sign of the priority value for them.
  E.g. `{ name: oro.security.filter.acl_privilege, priority: 100 }` should be changed to
  `{ name: oro.security.filter.acl_privilege, priority: -100 }`

#### ScopeBundle
* TRIGGER database privilege became required

#### SSOBundle
* The configuration option `oro_sso.enable_google_sso` was renamed to `oro_google_integration.enable_sso`.
* The configuration option `oro_sso.domains` was renamed to `oro_google_integration.sso_domains`.
* The service `oro_sso.oauth_provider` was renamed to `oro_sso.oauth_user_provider`.[[f]](#upgrade-toolkit-coverage)

#### UserBundle
* The name for `/api/authstatuses` REST API resource was changed to `/api/userauthstatuses`.
* The following changes were done in the `Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider` class:[[p]](#upgrade-toolkit-coverage)
  - the method `getPermissionCategories` was renamed to `getCategories`
  - the method `getTabList` was renamed to `getTabIds`
  - the following methods were removed `getAllCategories`, `getTabbedCategories`, `getCategory`,
    `addProvider`, `getProviders`, `getProviderByName`, `hasProvider`

#### UIBundle
* Modules of `jquery-ui` library are now declared separately, and each of them has to be imported directly, if necessary (`jquery-ui/widget`, `jquery-ui/widgets/sortable` etc.)
* Moved layout themes build artefacts from `public/layout-build/{theme}` to `public/build/{theme}` folder.
* Moved admin theme build artefacts from `public/build` to `public/build/admin` folder.
* Changed the output path for the admin theme from `css/oro/oro.css` to `css/oro.css`.
* Changed the output path for tinymce CSS entry points from `css/tinymce/*` to `to tinymce/*`.

#### webpack-config-builder

* All the JavaScript dev-dependencies, including webpack, karma, and eslint, are now managed on the application level. As a result, there is no need to install node modules in the `vendor/oro/platform/build` folder anymore. Now the application has only one node_modules folder - in the root directory. This allows application developers to take full control of the dev-dependencies in the project.
* The `webpack-config-builder` module was moved to a separate package and now is published at npmjs.com as `oro-webpack-config-builder` package.
  The package provides an integration of OroPlatform based applications with the Webpack.
* The `public/bundles/npmassets` folder was deleted. This folder contained the full copy of the node_modules folder,
  which is unnecessary with the webpack build. Now you have to reference node modules directly by their names.
  - To migrate the scss code and configuration, replace the `npmassets/` and `bundles/nmpassets` prefixes with `~` for all the node modules paths:

    **assets.yml**
    ```diff
    # ...
        inputs:
    -        - 'bundles/npmassets/slick-carousel/slick/slick.scss'
    +        - '~slick-carousel/slick/slick.scss'
    ```
    **\*.scss**
    ```diff     
     
    - @import "npmassets/bootstrap/scss/variables";
    + @import "~bootstrap/scss/variables";
    
    - @import "bundles/npmassets/bootstrap/scss/variables";
    + @import "~bootstrap/scss/variables";
    ```
  - To migrate the javascript code and configuration, drop `npmassets/` and `bundles/nmpassets` prefixes from the node module path.

    **jsmodules.yml**
    ```diff
    # ...
    - slick$: npmassets/slick-carousel/slick/slick
    + slick$: slick-carousel/slick/slick
    ```
    **\*.js**
    ```diff
    # ... 
    - import 'npmassets/focus-visible/dist/focus-visible';
    + import 'focus-visible/dist/focus-visible';
    # ...
    - require('bundles/npmassets/Base64/base64');
    + require('Base64/base64');
    ```
* To make an NPM library assets publicly available (e.g. some plugins of a library are have to be loaded dynamically in runtime) you can define in your module that an utilized library requires context:
  ```js
  require.context(
    '!file-loader?name=[path][name].[ext]]&outputPath=../_static/&context=tinymce!tinymce/plugins',
    true,
    /.*/
  );
  ```
  This way Webpack will copy `tinymce/plugins` folder into public directory `public/build/_static/_/node_modules/tinymce/plugins`.

  Pay attention for the leading exclamation point, it says that all other loaders (e.g. css-loader) should be ignored for this context.
  If you nevertheless need to process all included css files by Webpack -- leading `!` has to be removed.
* The "oomphinc/composer-installers-extender" composer package was removed. As a result, composer components are not copied automatically to the `public/bundles/components` directory.
  To copy files that are not handled by webpack automatically to the public folder, you can use approach with `require.context` described above.
* The "resolve-url-loader" NPM dependency was removed. Now you should always specify the valid relative or absolute path in SCSS files explicitly. The absolute path must start with `~`:
    ```diff
    # ... 
    # The relative path works the same. You only might need to fix typos, 
    # as the resolve-url-loader ignored them because of the magic global search feature.
    background-image: url(../../img/glyphicons-halflings.png);
    # ...
    # The path without `~` is a relative path
    $icomoon-font-path: "fonts" !default;
    # ... 
    # An absolute path should be prefixed with `~`
    - $icomoon-font-path: "fonts" !default;
    + $icomoon-font-path: "~bundles/orocms/fonts/grapsejs/fonts" !default;
    ```

### Removed

* Package `twig/extensions` is abandoned by its maintainers and has been removed from Oro dependencies.

#### ApiBundle
* The class `Oro\Bundle\ApiBundle\ApiDoc\RemoveSingleItemRestRouteOptionsResolver` and the service
  `oro_api.rest.routing_options_resolver.remove_single_item_routes` were removed.
  Exclude the `get` action in `Resources/config/oro/api.yml` instead.

#### CacheBundle
* The service "oro.file_cache.abstract" was removed because it is not used anywhere.

#### EntityExtendBundle
* The `origin` option was removed from entity and field configuration.
* The `ORIGIN_CUSTOM` and `ORIGIN_SYSTEM` constants were removed from `Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope`.
* The `skip-origin` argument was removed from the `oro:entity-extend:update-config` CLI command.

### FilterBundle
* The outdated filter `selectrow` was removed, as well as `Oro\Bundle\FilterBundle\Filter\SelectRowFilter`
  and `Oro\Bundle\FilterBundle\Form\Type\Filter\SelectRowFilterType` classes.
* The outdated filter `many-to-many` was removed, as well as `Oro\Bundle\FilterBundle\Filter\ManyToManyFilter`
  and `Oro\Bundle\FilterBundle\Form\Type\Filter\ManyToManyFilterType` classes.

#### ImportExportBundle
* The `unique_job_slug` MQ message parameter was removed for `oro.importexport.pre_import` topic. 

### SyncBundle
* Removed long-unused the `orosync/js/content/grid-builder` component from the layout updates.

#### UIBundle
* The `collectionField` TWIG macros was removed. Use the `form_row_collection` TWIG function instead.
  Before: `UI.collectionField(form.emails, 'oro.user.emails.label'|trans)`.
  After: `form_row_collection(form.emails)`.
  To change "add" button label use the `add_label` form option.
* Removed `cssVariablesManager.getVariables()` method as unused, and deleted dependency on the [jhildenbiddle/css-vars-ponyfill](https://github.com/jhildenbiddle/css-vars-ponyfill) library. 

#### UserBundle
* The `Oro\Bundle\UserBundle\Provider\PrivilegeCategoryProviderInterface` was removed.
  Use `Resources/config/oro/acl_categories.yml` files to configure ACL categories.
* Email template `user_reset_password_as_admin` has been removed. Use `force_reset_password` instead.


## 4.1.0 (2020-01-31)
[Show detailed list of changes](incompatibilities-4-1.md)


### Added

#### AttachmentBundle
* Added *MultiImage* and *MultiField* field types to Entity Manager. Read more in [documentation](https://doc.oroinc.com/bundles/platform/AttachmentBundle/).

### Changed

#### ActivityBundle
* The DIC tag `oro_activity.activity_entity_delete_handler` was removed.
  Use decoration of `oro_activity.activity_entity_delete_handler_extension` service to instead.
* The interface `Oro\Bundle\ActivityBundle\Entity\Manager\ActivityEntityDeleteHandlerInterface` was removed.
  Use `Oro\Bundle\ActivityBundle\Handler\ActivityEntityDeleteHandlerExtensionInterface` instead.[[p]](#upgrade-toolkit-coverage)

#### ApiBundle
* The section `relations` was removed from `Resources/config/oro/api.yml`. The action `get_relation_config` that
  was responsible to process this section was removed as well.
  This section was not used to build API that conforms JSON:API specification that is the main API type.
  In case if you need a special configuration for "plain" REST API, you can define it in
  `Resources/config/oro/api_plain.yml` configuration files or create a processor for the `get_config` action.
* The `delete_handler` configuration option was removed.
  The `Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry` class is used to get the deletion handler instead.
* The class `Oro\Bundle\ApiBundle\Request\ApiActions` was renamed to `Oro\Bundle\ApiBundle\Request\ApiAction`.[[f]](#upgrade-toolkit-coverage)
* The constant `NORMALIZE_RESULT_GROUP` was removed from
  `Oro\Bundle\ApiBundle\Processor\NormalizeResultActionProcessor`
  Use `NORMALIZE_RESULT` constant from `Oro\Bundle\ApiBundle\Request\ApiActionGroup` instead.[[f]](#upgrade-toolkit-coverage)
* The following classes were moved from `Oro\Bundle\ApiBundle\Config` namespace to `Oro\Bundle\ApiBundle\Config\Extension`:[[f]](#upgrade-toolkit-coverage)
    - ConfigExtensionInterface
    - AbstractConfigExtension
    - ConfigExtensionRegistry
    - FeatureConfigurationExtension
    - ActionsConfigExtension
    - FiltersConfigExtension
    - SortersConfigExtension
    - SubresourcesConfigExtension
* The following classes were moved from `Oro\Bundle\ApiBundle\Config` namespace to `Oro\Bundle\ApiBundle\Config\Extra`:[[f]](#upgrade-toolkit-coverage)
    - ConfigExtraInterface
    - ConfigExtraSectionInterface
    - ConfigExtraCollection
    - CustomizeLoadedDataConfigExtra
    - DataTransformersConfigExtra
    - DescriptionsConfigExtra
    - EntityDefinitionConfigExtra
    - ExpandRelatedEntitiesConfigExtra
    - FilterFieldsConfigExtra
    - FilterIdentifierFieldsConfigExtra
    - FiltersConfigExtra
    - MaxRelatedEntitiesConfigExtra
    - MetaPropertiesConfigExtra
    - RootPathConfigExtra
    - SortersConfigExtra
* The following classes were moved from `Oro\Bundle\ApiBundle\Config` namespace to `Oro\Bundle\ApiBundle\Config\Loader`:[[f]](#upgrade-toolkit-coverage)
    - ConfigLoaderInterface
    - AbstractConfigLoader
    - ConfigLoaderFactory
    - ConfigLoaderFactoryAwareInterface
    - ActionsConfigLoader
    - EntityDefinitionConfigLoader
    - EntityDefinitionFieldConfigLoader
    - FiltersConfigLoader
    - SortersConfigLoader
    - StatusCodesConfigLoader
    - SubresourcesConfigLoader
* The following classes were moved from `Oro\Bundle\ApiBundle\Metadata` namespace to `Oro\Bundle\ApiBundle\Metadata\Extra`:[[f]](#upgrade-toolkit-coverage)
    - MetadataExtraInterface
    - MetadataExtraCollection
    - ActionMetadataExtra
    - HateoasMetadataExtra
* All processors from `Oro\Bundle\ApiBundle\Processor\Config\GetConfig`
  and `Oro\Bundle\ApiBundle\Processor\Config\Shared` namespaces were moved
  to `Oro\Bundle\ApiBundle\Processor\GetConfig` namespace.[[f]](#upgrade-toolkit-coverage)
* The class `ConfigProcessor` was moved from `Oro\Bundle\ApiBundle\Processor\Config` namespace
  to `Oro\Bundle\ApiBundle\Processor\GetConfig` namespace.[[f]](#upgrade-toolkit-coverage)
* The class `ConfigContext` was moved from `Oro\Bundle\ApiBundle\Processor\Config` namespace
  to `Oro\Bundle\ApiBundle\Processor\GetConfig` namespace.[[f]](#upgrade-toolkit-coverage)
* The priority of `oro_api.validate_included_forms` processor was changed from `-70` to `-68`.
* The priority of `oro_api.validate_form` processor was changed from `-90` to `-70`.
* The priority of `oro_api.post_validate_included_forms` processor was changed from `-96` to `-78`.
* The priority of `oro_api.post_validate_form` processor was changed from `-97` to `-80`.

#### AssetBundle
* The new feature, Hot Module Replacement (HMR or Hot Reload) enabled for SCSS. To enable HMR for custom CSS links, please [follow the documentation](https://doc.oroinc.com/bundles/platform/AssetBundle/).

#### ConfigBundle
* The handling of `priority` attribute for `oro_config.configuration_search_provider` DIC tag
  was changed to correspond Symfony recommendations.
  If you have services with this tag, change the sign of the priority value for them.[[f]](#upgrade-toolkit-coverage)
  E.g. `{ name: oro_config.configuration_search_provider, priority: 100 }` should be changed to
  `{ name: oro_config.configuration_search_provider, priority: -100 }`

#### DataGridBundle
* The handling of `priority` attribute for `oro_datagrid.extension.action.provider` and
  `oro_datagrid.extension.mass_action.iterable_result_factory` DIC tags was changed to correspond Symfony recommendations.
  If you have services with these tags, change the sign of the priority value for them.[[f]](#upgrade-toolkit-coverage)
  E.g. `{ name: oro_datagrid.extension.action.provider, priority: 100 }` should be changed to
  `{ name: oro_datagrid.extension.action.provider, priority: -100 }`

#### InstallerBundle

* JS dependencies management has been moved from [Asset Packagist](https://asset-packagist.oroinc.com/) to
Composer + NPM solution. So the corresponding Asset Packagist entry in the `repositories` section of `composer.json`
must be removed.

	If there are bower or npm dependencies (packages with names starting with `bower-asset/` or `npm-asset/`) specified in your `composer.json`, then do the following:

	1) for package names starting with `npm-asset/`: remove the `npm-asset/` prefix, move the dependency to the `extra.npm` section
  of `composer.json`;
	2) for package names starting with `bower-asset/`: remove the `bower-asset/` prefix, find the corresponding or alternative
 npm packages instead of bower packages, and add them to the `extra.npm` section of `composer.json`.

	If you have your own `package.json` with npm dependencies, then move them to the `extra.npm` section of `composer.json`.
	If you need a custom script to be executed as well, then you can add your custom script to the `scripts` section of `composer.json`.
	
#### NavigationBundle
* The service `kernel.listener.nav_history_response` was renamed to `oro_navigation.event_listener.navigation_history`.
* The service `kernel.listener.hashnav_response` was renamed to `oro_navigation.event_listener.hash_navigation`.

#### OrganizationBundle
* The constant `SCOPE_KEY` in `Oro\Bundle\OrganizationBundle\Provider\ScopeOrganizationCriteriaProvider`
  was replaced with `ORGANIZATION`.[[f]](#upgrade-toolkit-coverage)

#### ScopeBundle
* The method `getCriteriaByContext()` was removed from `Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface`.
* The method `getCriteriaForCurrentScope()` in `Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface`
  was replaced with `getCriteriaValue()`.[[f]](#upgrade-toolkit-coverage)
* The class `Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider` was removed.
  Use direct implementation of `Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface` in your providers.

#### SecurityBundle
* The interface `Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface`
  was renamed to `Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface`.[[f]](#upgrade-toolkit-coverage)
  Also methods `getOrganizationContext` and `setOrganizationContext` were renamed to
  `getOrganization` and `setOrganization`.[[f]](#upgrade-toolkit-coverage)
* The class `Oro\Bundle\SecurityBundle\Exception\ForbiddenException` was removed.
  Use `Symfony\Component\Security\Core\Exception\AccessDeniedException` instead.[[f]](#upgrade-toolkit-coverage)

#### SoapBundle
* The interface `Oro\Bundle\SoapBundle\Handler\DeleteHandlerInterface` was replaced with
  `Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerInterface`
  and `Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtensionInterface`.[[p]](#upgrade-toolkit-coverage)

#### TranslationBundle
* The handling of `priority` attribute for `oro_translation.extension.translation_context_resolver` and
  `oro_translation.extension.translation_strategy` DIC tags was changed to correspond Symfony recommendations.[[f]](#upgrade-toolkit-coverage)
  If you have services with these tags, change the sign of the priority value for them.
  E.g. `{ name: oro_translation.extension.translation_context_resolver, priority: 100 }` should be changed to
  `{ name: oro_translation.extension.translation_context_resolver, priority: -100 }`

#### UserBundle
* The constant `SCOPE_KEY` in `Oro\Bundle\UserBundle\Provider\ScopeUserCriteriaProvider`
  was replaced with `USER`.[[f]](#upgrade-toolkit-coverage)

#### WorkflowBundle
* The handling of `priority` attribute for `oro.workflow.configuration.handler` and
  `oro.workflow.definition_builder.extension` DIC tags was changed to correspond Symfony recommendations.[[f]](#upgrade-toolkit-coverage)
  If you have services with these tags, change the sign of the priority value for them.
  E.g. `{ name: oro.workflow.configuration.handler, priority: 100 }` should be changed to
  `{ name: oro.workflow.configuration.handler, priority: -100 }`



### Removed
* `*.class` parameters for all entities were removed from the dependency injection container.
The entity class names should be used directly, e.g. `'Oro\Bundle\EmailBundle\Entity\Email'`
instead of `'%oro_email.email.entity.class%'` (in service definitions, datagrid config files, placeholders, etc.), and
`\Oro\Bundle\EmailBundle\Entity\Email::class` instead of `$container->getParameter('oro_email.email.entity.class')`
(in PHP code).
* All `*.class` parameters for service definitions were removed from the dependency injection container.


#### ActivityListBundle
* The `getActivityClass()` method was removed from `Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface`.
  Use the `class` attribute of the `oro_activity_list.provider` DIC tag instead.
* The `getAclClass()` method was removed from `Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface`.
  Use the `acl_class` attribute of the `oro_activity_list.provider` DIC tag instead.

#### DataGridBundle
* The `getName()` method was removed from `Oro\Bundle\DataGridBundle\Extension\Board\Processor\BoardProcessorInterface`.
  Use the `alias` attribute of the `oro_datagrid.board_processor` DIC tag instead.
* The DIC parameter `oro_datagrid.extension.orm_sorter.class` was removed.
  If you use `%oro_datagrid.extension.orm_sorter.class%::DIRECTION_ASC`
  or `%oro_datagrid.extension.orm_sorter.class%::DIRECTION_DESC` in `Resources/config/oro/datagrids.yml`,
  replace them to `ASC` and `DESC` strings.[[f]](#upgrade-toolkit-coverage)
* The deprecated constant `Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_PATH` was removed.
  Use `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::DATASOURCE_PATH` instead.[[f]](#upgrade-toolkit-coverage)
* The deprecated constant `Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_TYPE_PATH` was removed.
  Use `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::DATASOURCE_TYPE_PATH`
  and `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::getDatasourceType()` instead.[[p]](#upgrade-toolkit-coverage)
* The deprecated constant `Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_ACL_PATH` was removed.
  Use `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::ACL_RESOURCE_PATH`
  and `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::getAclResource()` instead.[[p]](#upgrade-toolkit-coverage)
* The deprecated constant `Oro\Bundle\DataGridBundle\Datagrid\Builder::BASE_DATAGRID_CLASS_PATH` was removed.
  Use `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::BASE_DATAGRID_CLASS_PATH` instead.[[f]](#upgrade-toolkit-coverage)
* The deprecated constant `Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_SKIP_ACL_CHECK` was removed.
  Use `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::DATASOURCE_SKIP_ACL_APPLY_PATH`
  and `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::isDatasourceSkipAclApply()` instead.[[p]](#upgrade-toolkit-coverage)
* The deprecated constant `Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_SKIP_COUNT_WALKER_PATH` was removed.
  Use `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::DATASOURCE_SKIP_COUNT_WALKER_PATH` instead.[[f]](#upgrade-toolkit-coverage)
* The deprecated class `Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper`
  and service `oro_datagrid.grid_configuration.helper` were removed.

#### EntityConfigBundle
* The `getType()` method was removed from `Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface`.
  Use the `type` attribute of the `oro_entity_config.attribute_type` DIC tag instead.
* The deprecated class `Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent` was removed.
  It was replaced with `Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent`.[[f]](#upgrade-toolkit-coverage)
* The deprecated class `Oro\Bundle\EntityConfigBundle\Event\FlushConfigEvent` was removed.
  It was replaced with `Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent`.[[f]](#upgrade-toolkit-coverage)

#### EntityExtendBundle
* Removed *HTML* field type, all HTML fields were converted to Text fields.

#### Math component
* The deprecated method `Oro\Component\Math\BigDecimal::withScale()` was removed. Use `toScale()` method instead.[[f]](#upgrade-toolkit-coverage)

#### MigrationBundle
* The deprecated method `Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension::put()` was removed. Use `set()` method instead.[[f]](#upgrade-toolkit-coverage)
* The deprecated constants `MAIN_FIXTURES_PATH` and `DEMO_FIXTURES_PATH` were removed from `Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand`.
  Use `oro_migration.locator.fixture_path_locator` service instead.

#### QueryDesignerBundle
* The deprecated constant `Oro\Bundle\QueryDesignerBundle\Grid\Extension\OrmDatasourceExtension::NAME_PATH` was removed.

#### ReminderBundle
* The `getName()` method was removed from `Oro\Bundle\ReminderBundle\Model\SendProcessorInterface`.
  Use the `method` attribute of the `oro_reminder.send_processor` DIC tag instead.

#### RequireJsBundle
* The bundle was completely removed, see [tips](https://doc.oroinc.com/bundles/platform/AssetBundle/#migration-from-requirejs-to-jsmodules) how to migrate to Webpack builder

#### SoapBundle
* The deprecated `Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpEntityNameParameterFilter` class was removed. Use `Oro\Bundle\SoapBundle\Request\Parameters\Filter\EntityClassParameterFilter` instead.[[f]](#upgrade-toolkit-coverage)

#### SecurityBundle
* The deprecated method `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface::getGlobalOwnerFieldName()` was removed. Use `getOrganizationFieldName()` method instead.[[f]](#upgrade-toolkit-coverage)

#### TagBundle
* The deprecated constant `Oro\Bundle\TagBundle\Grid\AbstractTagsExtension::GRID_NAME_PATH` was removed.

#### TranslationBundle
* The deprecated option `is_translated_group` for `Symfony\Component\Form\Extension\Core\Type\ChoiceType` was removed.
  Use `translatable_groups` option instead.
* The deprecated option `is_translated_option` for `Symfony\Component\Form\Extension\Core\Type\ChoiceType` was removed.
  Use `translatable_options` option instead.

#### UIBundle
* The `getName()` method was removed from `Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface`.
  Use the `alias` attribute of the `oro_ui.content_provider` DIC tag instead.
* Unneeded `isEnabled()` and `setEnabled()` methods were removed from `Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface`.






## 4.0.0 (2019-07-31)
[Show detailed list of changes](incompatibilities-4-0.md)


### Added

#### ApiBundle
* The class `Oro\Bundle\ApiBundle\Request\ValueTransformer` (service ID is `oro_api.value_transformer`) was added
  to help transformation of complex computed values to concrete data-type for API responses.
  
#### UIBundle

* CSSVariable parser `oroui/js/css-variables-manager` has been add. Source module [css-variables-manager](./src/Oro/Bundle/UIBundle/Resources/public/js/css-variables-manager.js)

  Github link [https://github.com/jhildenbiddle/css-vars-ponyfill](https://github.com/jhildenbiddle/css-vars-ponyfill)

### Changed

#### ApiBundle
* The handling of HTTP response status code `403 Forbidden` was fixed. Now this status code is returned if there are
  no permissions to use an API resource. Before the fix `404 Not Found` status code was returned in both cases,
  when an entity did not exist and when there were no permissions to operate with it.
* The service `oro_api.entity_serializer.acl_filter` was renamed to `oro_api.entity_serializer.field_filter`.
* The method `normalizeObject` of `Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer`
  was replaced with `normalizeObjects`.

#### CacheBundle
* The approach based on `Oro\Bundle\CacheBundle\Loader\ConfigurationLoader` and `Oro\Component\Config\Dumper\CumulativeConfigMetadataDumper` has been replaced with the approach based on `Oro\Component\Config\Cache\PhpConfigProvider`.

#### ChainProcessor component
* The interface `Oro\Component\ChainProcessor\ProcessorFactoryInterface` was replaced with
  `Oro\Component\ChainProcessor\ProcessorRegistryInterface`.
* The class `Oro\Component\ChainProcessor\ChainProcessorFactory` was removed.
  Use the decoration to create a chain of processor registries instead.

#### Config component
* The methods `load()` and `registerResources()` of class `Oro\Component\Config\Loader\CumulativeConfigLoader`
  were changed to not accept `Symfony\Component\DependencyInjection\ContainerBuilder` as resources container.
  Use `Oro\Component\Config\Loader\ContainerBuilderAdapter` to adapt
  `Symfony\Component\DependencyInjection\ContainerBuilder` to `Oro\Component\Config\ResourcesContainerInterface`.

#### EmailBundle
* The `Oro\Bundle\EmailBundle\Provider\EmailRenderer` was reimplemented to support computed variables.
  The following changes were made:
    - remove extending of this class from `Twig_Environment`
    - method `renderWithDefaultFilters` was renamed to `renderTemplate`
    - move loading of configuration to `Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProvider`
    - move rendering of template to `Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRenderer`
    - move formatting of entity related variables to `Oro\Bundle\EntityBundle\Twig\Sandbox\EntityFormatExtension`
* The interface `Oro\Bundle\EmailBundle\Processor\VariableProcessorInterface` was moved to
  `Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorInterface`.
  The method `process` was changed from `process($variable, array $definition, array $data = [])`
  to `process(string $variable, array $processorArguments, TemplateData $data): void`.
  This allows processors to add computed values.
* The interface `Oro\Bundle\EmailBundle\Provider\SystemVariablesProviderInterface` was moved to
  `Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesProviderInterface`.
  The method `getVariableDefinitions` was changed from `getVariableDefinitions()` to `getVariableDefinitions(): array`.
  The method `getVariableValues` was changed from `getVariableValues()` to `getVariableValues(): array`.
* The interface `Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface` was moved to
  `Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface`.
  The method `getVariableDefinitions` was changed from `getVariableDefinitions($entityClass = null)`
  to `getVariableDefinitions(): array`.
  The method `getVariableGetters` was changed from `getVariableGetters($entityClass = null)`
  to `getVariableGetters(): array`.
  By performance reasons new method `getVariableProcessors(string $entityClass): array` was added.
  If method `getVariableDefinitions` of your provider returns info about processors, move it to `getVariableProcessors`.
* Due to the updated version of `symfony/swiftmailer-bundle` parameter `mailer_transport: mail` is not supported anymore. Using old transport will cause such an exception -
 `Unable to replace alias “swiftmailer.mailer.default.transport.real” with actual definition “mail”.
  You have requested a non-existent service “mail”.` Please
  use `mailer_transport: sendmail` instead or another available swiftmailer transport type.
  
* In `Oro\Bundle\EmailBundle\Controller\EmailController::checkSmtpConnectionAction`
 (`oro_email_check_smtp_connection` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EmailBundle\Controller\EmailController::purgeEmailsAttachmentsAction`
 (`oro_email_purge_emails_attachments` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EmailBundle\Controller\EmailController::linkAction`
 (`oro_email_attachment_link` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EmailBundle\Controller\EmailController::userEmailsSyncAction`
 (`oro_email_user_sync_emails` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EmailBundle\Controller\EmailController::toggleSeenAction`
 (`oro_email_toggle_seen` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EmailBundle\Controller\EmailController::markSeenAction`
 (`oro_email_mark_seen` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EmailBundle\Controller\EmailController::markAllEmailsAsSeenAction`
 (`oro_email_mark_all_as_seen` route)
 action the request method was changed to POST.

#### EmbeddedFormBundle
* In `Oro\Bundle\EmbeddedFormBundle\Controller\EmbeddedFormController::deleteAction`
 (`oro_embedded_form_delete` route)
 action the request method was changed to DELETE.
* In `Oro\Bundle\EmbeddedFormBundle\Controller\EmbeddedFormController::defaultDataAction`
 (`oro_embedded_form_default_data` route)
 action the request method was changed to POST.

#### EntityBundle
* In `Oro\Bundle\EntityBundle\Controller\EntitiesController::deleteAction`
 (`oro_entity_delete` route)
 action the request method was changed to DELETE.

#### EntityConfigBundle
* In `Oro\Bundle\EntityConfigBundle\Controller\AttributeController::removeAction`
 (`oro_attribute_remove` route)
 action the request method was changed to DELETE.
* In `Oro\Bundle\EntityConfigBundle\Controller\AttributeController::unremoveAction`
 (`oro_attribute_unremove` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EntityConfigBundle\Controller\AttributeFamilyController::deleteAction`
 (`oro_attribute_family_delete` route)
 action the request method was changed to DELETE.

#### EntityExtendBundle
* In `Oro\Bundle\EntityExtendBundle\Controller\ConfigEntityGridController::removeAction`
 (`oro_entityextend_entity_remove` route)
 action the request method was changed to DELETE.
* In `Oro\Bundle\EntityExtendBundle\Controller\ConfigEntityGridController::unremoveAction`
 (`oro_entityextend_field_unremove` route)
 action the request method was changed to POST.
  
#### EntitySerializer component
* The interface `Oro\Component\EntitySerializer\Filter\EntityAwareFilterInterface` was renamed to
  `Oro\Component\EntitySerializer\FieldFilterInterface` and the following changes was made in it:
    - the constants `FILTER_ALL`, `FILTER_VALUE` and `FILTER_NOTHING` were removed
    - the method `checkField` was changed from `checkField(object|array $entity, string $entityClass, string $field): int`
      to `checkField(object $entity, string $entityClass, string $field): ?bool`
* The class `Oro\Component\EntitySerializer\Filter\EntityAwareFilterChain` and the service
  `oro_security.serializer.filter_chain` were removed.
  Use decoration of `oro_security.entity_serializer.field_filter` and/or `oro_api.entity_serializer.field_filter`
  services instead.
* The method `setFieldsFilter` of `Oro\Component\EntitySerializer\EntitySerializer` was renamed to `setFieldFilter`.
* The method `transform` of `Oro\Component\EntitySerializer\DataTransformerInterface` was changed
  from `transform($class, $property, $value, array $config, array $context)`
  to `transform($value, array $config, array $context)`.
* The class `Oro\Component\EntitySerializer\EntityDataTransformer` was renamed to `Oro\Component\EntitySerializer\DataTransformer`
  and `$baseDataTransformer` property was removed from it.
* The execution of post serialize collection handlers was added to `to-one` associations; now both
  single item and collection post serialize handlers are executed for all types of associations.
* The execution of post serialize handlers was removed for associations in case only ID field is requested for them.

#### ImportExportBundle
* In `Oro\Bundle\ImportExportBundle\Controller\ImportExportController::importValidateAction`
 (`oro_importexport_import_validate` route)
 action the request method was changed to POST.
* In `Oro\Bundle\ImportExportBundle\Controller\ImportExportController::importProcessAction`
 (`oro_importexport_import_process` route)
 action the request method was changed to POST.
* In `Oro\Bundle\ImportExportBundle\Controller\ImportExportController::instantExportAction`
 (`oro_importexport_export_instant` route)
 action the request method was changed to POST.
* Introduced concept of import/export owner. Applied approach with role-based owner-based permissions to the export and import functionality.
* Option `--email` has become required for `oro:import:file` command.
* Removed Message Queue Topics and related Processors. All messages with this topics will be rejected:
    * `oro.importexport.send_import_error_notification`
    * `oro.importexport.import_http_preparing`
    * `oro.importexport.import_http_validation_preparing`
    * `oro.importexport.pre_cli_import`, should be used `oro.importexport.pre_import` instead.
    * `oro.importexport.cli_import` , should be used `oro.importexport.import` instead.

#### IntegrationBundle
* In `Oro\Bundle\IntegrationBundle\Controller\IntegrationController::scheduleAction`
 (`oro_integration_schedule` route)
 action the request method was changed to POST.

#### MessageQueueBundle
* In `Oro\Bundle\MessageQueueBundle\Controller\Api\Rest\JobController::interruptRootJobAction`
 (`/api/rest/{version}/message-queue/job/interrupt/{id}` path)
 action the request method was changed to POST.
 
#### SecurityBundle
* The class `Oro\Bundle\SecurityBundle\Filter\SerializerFieldFilter` was renamed to
  `Oro\Bundle\SecurityBundle\Filter\EntitySerializerFieldFilter`.
* The service `oro_security.serializer.acl_filter` was renamed to `oro_security.entity_serializer.field_filter`.

#### UIBundle

* viewportManager has been updated. Add sync with CSS breakpoint variables
* The redundant methods `getFormatterName`, `getSupportedTypes` and `isDefaultFormatter` were removed from `Oro\Bundle\UIBundle\Formatter\FormatterInterface`.
  Use `data_type` attribute of `oro_formatter` tag to specify the default formatter for the data type.

#### UserBundle
 * API processor `oro_user.api.create.save_entity` was renamed to `oro_user.api.create.save_user`.

#### WorkflowBundle
* In `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\ProcessController::activateAction`
 (`/api/rest/{version}/process/activate/{processDefinition}` path)
 action the request method was changed to POST.
* In `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\ProcessController::deactivateAction`
 (`/api/rest/{version}/process/deactivate/{processDefinition}` path)
 action the request method was changed to POST.
* In `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::startAction`
 (`/api/rest/{version}/workflow/start/{workflowName}/{transitionName}` path)
 action the request method was changed to POST.
* In `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::transitAction`
 (`/api/rest/{version}/workflow/transit/{workflowName}/{transitionName}` path)
 action the request method was changed to POST.
* In `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::activateAction`
 (`/api/rest/{version}/workflow/activate/{workflowName}/{transitionName}` path)
 action the request method was changed to POST.
* In `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::deactivateAction`
 (`/api/rest/{version}/workflow/deactivate/{workflowName}/{transitionName}` path)
 action the request method was changed to POST.
 
 
 
### Removed

#### ActionBundle
* The deprecated `route_exists` action (class `Oro\Bundle\ActionBundle\Condition\RouteExists`) was removed.

#### ApiBundle
* All filters and sorters were removed for all "relationships" resources that returns a collection,
  e.g. "GET /api/countries/{id}/relationships/regions". If you need filtered or sorted data, use sub-resources
  instead of relationships, e.g. "GET /api/countries/{id}/regions".
* The parameter `$usePropertyPathByDefault` was removed from `getResultFieldName` method of `Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext`.

#### ChartBundle
* The possibility to define charts via ChartBundle configuration has been removed. Use `Resources/config/oro/charts.yml` instead.
* Methods `getConfigs()` and `getChartConfigs()` have been removed from `Oro\Bundle\ChartBundle\Model\ConfigProvider`. Use `getChartNames()` and `getChartConfig($chartName)` methods instead.

#### ConfigBundle
* Not used tag `oro_config.configuration_provider` has been removed.

#### DashboardBundle
* The `dashboards`, `widgets` and `widgets_configuration` sections have been removed from DashboardBundle configuration. Use `Resources/config/oro/dashboards.yml` instead.
* Methods `getConfigs()`, `getConfig($key)` and `hasConfig($key)` have been removed from `Oro\Bundle\DashboardBundle\Model\ConfigProvider`.

#### DependencyInjection component
* The `ServiceLinkRegistry` and all relates classes was removed.
  To define a bag of lazy loaded services use Symfony [Service Locator](https://symfony.com/doc/3.4/service_container/service_subscribers_locators.html#defining-a-service-locator).
  The list of removed classes:
    - `Oro\Component\DependencyInjection\ServiceLinkRegistry`
    - `Oro\Component\DependencyInjection\ServiceLinkRegistryAwareInterface`
    - `Oro\Component\DependencyInjection\ServiceLinkRegistryAwareTrait`
    - `Oro\Component\DependencyInjection\Compiler\TaggedServiceLinkRegistryCompilerPass`
    - `Oro\Component\DependencyInjection\Exception\UnknownAliasException`

#### EmbeddedFormBundle
* Layout context parameter `embedded_form_custom_layout` has been removed. Use layout updates instead.

#### EntityBundle
* The `exclusions`, `entity_aliases`, `entity_alias_exclusions`, `virtual_fields`, `virtual_relations` and `entity_name_formats` sections have been removed from EntityBundle configuration. Use `Resources/config/oro/entity.yml` instead.

#### FilterBundle
* The `datasource` attribute for `oro_filter.extension.orm_filter.filter` tag has been removed as it is redundant.

#### HelpBundle
* The possibility to define `resources`, `vendors` and `routes` sections via HelpBundle configuration has been removed. Use `Resources/config/oro/help.yml` instead.

#### MessageQueueBundle
* The `DefaultTransportFactory` and related configuration option `oro_message_queue.transport.defaut` was removed. Check `config/config.yml` in your application.

#### NavigationBundle
* The possibility to define `menu_config`, `navigation_elements` and `titles` sections via NavigationBundle configuration has been removed. Use `Resources/config/oro/navigation.yml` instead.
* The class `Oro\Bundle\NavigationBundle\Config\MenuConfiguration` has been removed. Use `Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider` instead.

#### LayoutBundle
* The `themes` section has been removed from LayoutBundle configuration. Use `Resources/views/layouts/{folder}/theme.yml` instead.

#### LocaleBundle
* The `name_format` section has been removed from LocaleBundle configuration. Use `Resources/config/oro/name_format.yml` instead.
* The `address_format` section has been removed from LocaleBundle configuration. Use `Resources/config/oro/address_format.yml` instead.
* The `locale_data` section has been removed from LocaleBundle configuration. Use `Resources/config/oro/locale_data.yml` instead.

#### QueryDesignerBundle
* The `oro_query_designer.query_designer.manager.link` service has been removed. Use `oro_query_designer.query_designer.manager` service instead.

#### SearchBundle
* The `datasource` attribute for `oro_search.extension.search_filter.filter` tag has been removed as it is redundant.
* The `entities_config` section has been removed from SearchBundle configuration. Use `Resources/config/oro/search.yml` instead.
* Not used event `Oro\Bundle\SearchBundle\Event\BeforeMapObjectEvent` has been removed.
* Deprecated DIC parameter `oro_search.entities_config` has been removed. Use `oro_search.provider.search_mapping` service instead of it.
* The following deprecated methods were removed from `Oro\Bundle\SearchBundle\Query\Query`:
    - andWhere
    - orWhere
    - where
    - getOptions
    - setMaxResults
    - getMaxResults
    - setFirstResult
    - getFirstResult
    - setOrderBy
    - getOrderBy
    - getOrderType
    - getOrderDirection
* The deprecated trait `Oro\Bundle\SearchBundle\EventListener\IndexationListenerTrait` was removed.
* The deprecated trait `Oro\Bundle\SearchBundle\Engine\Orm\DBALPersisterDriverTrait` was removed.

#### SecurityBundle
* The command `security:configurable-permission:load` has been removed.
* Twig function `resource_granted` has been removed. Use `is_granted` from Symfony instead.

#### SidebarBundle
* The `sidebar_widgets` section has been removed from SidebarBundle configuration. Use `Resources/public/sidebar_widgets/{folder}/widget.yml` instead.
* The class `Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry` has been renamed to `Oro\Bundle\SidebarBundle\Configuration\WidgetDefinitionProvider`.
* The service `oro_sidebar.widget_definition.registry` has been renamed to `oro_sidebar.widget_definition_provider`.

#### UIBundle
* The `placeholders` and `placeholder_items` sections have been removed from UIBundle configuration. Use `Resources/config/oro/placeholders.yml` instead.
* Deprecated option `show_pin_button_on_start_page` has been removed from UIBundle configuration.
* Plugin `jquery.mCustomScrollbar` has been removed. Use [styled-scroll-bar](./src/Oro/Bundle/UIBundle/Resources/public/js/app/plugins/styled-scroll-bar.js)


### Deprecated
#### ImportExportBundle
* Message Queue Topic `oro.importexport.pre_http_import` is deprecated in favor of `oro.importexport.pre_import`.
* Message Queue Topic `oro.importexport.http_import` is deprecated in favor of `oro.importexport.import`.



## 3.1.4

### Removed
#### InstallerBundle
* Commands `oro:platform:upgrade20:db-configs` and `oro:platform:upgrade20` were removed because they are no longer used in version 3.x. Related logic was also removed. Use `oro:platform:update` instead.
* Service `oro_installer.namespace_migration` and the logic that used it were removed.

#### WorkflowBundle
* Command `oro:workflow:definitions:upgrade20` was removed because it was used for 2.x version update only.

## 3.1.3 (2019-02-19)

## 3.1.2 (2019-02-05)

## 3.1.0 (2019-01-30)
[Show detailed list of changes](incompatibilities-3-1.md)

### Added

#### ApiBundle
* Added `custom_fields` as a possible value for `exclusion_policy` option of `entities` section of `Resources/config/oro/api.yml`. This value can be used if it is required to exclude all custom fields (fields with `is_extend` = `true` and `owner` = `Custom` in `extend` scope in entity configuration) that are not configured explicitly.
* Enable filters for to-many associations. The following operators are implemented: `=` (`eq`), `!=` (`neq`), `*` (`exists`), `!*` (`neq_or_null`), `~` (`contains`) and `!~` (`not_contains`).
* Added [documentation about filters](https://doc.oroinc.com/3.1/backend/api/filters/#api-filters).
* Added data flow diagrams for public actions. See [Actions](https://doc.oroinc.com/3.1/backend/api/actions/#web-api-actions).
* Added `rest_api_prefix` and `rest_api_pattern` configuration options and `oro_api.rest.prefix` and `oro_api.rest.pattern` DIC parameters to be able to reconfigure REST API base path.
* Added trigger `disposeLayout` on DOM element in `layout`

#### AssetBundle
* `AssetBundle` replaces the deprecated `AsseticBundle` to build assets using Webpack.
It currently supports only styles assets. JS assets are still managed by [OroRequireJsBundle](https://doc.oroinc.com/3.1/backend/bundles/platform/RequireJSBundle/).


#### AttachmentBundle
* Added possibility to set available mime types from configuration.
To add or remove available mime types, add changes to the `upload_file_mime_types` section and `upload_image_mime_types` in the config.yml file:

```yml
oro_attachment:
    upload_file_mime_types:
        - application/msword
        - application/vnd.ms-excel
        - application/pdf
        - application/zip
        - image/gif
        - image/jpeg
        - image/png
    upload_image_mime_types:
        - image/gif
        - image/jpeg
        - image/png
```

#### DatagridBundle
* Added [Datagrid Settings](https://github.com/oroinc/platform/blob/3.1/src/Oro/Bundle/DataGridBundle/Resources/doc/frontend/datagrid_settings.md) functionality for flexible managing of filters and grid columns

#### CacheBundle
* Added `oro.cache.abstract.without_memory_cache` that is the same as `oro.cache.abstract` but without using additional in-memory caching, it can be used to avoid unnecessary memory usage and performance penalties if in-memory caching is not needed, e.g. you implemented some more efficient in-memory caching strategy around your cache service.

#### SecurityBundle
* Added `Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension` trait that can be used in functional tests where you need to change permissions for security roles.

#### UIBundle
* Added the `addBeforeActionPromise` static method of `BaseController` in JS which enables to postpone route action if the required async process is in progress.


### Changed

#### AddressBundle
* Changes in `/api/addresses` REST API resource:
    - the attribute `created` was renamed to `createdAt`
    - the attribute `updated` was renamed to `updatedAt`
    
#### AssetBundle
* Syntax of `Resources/config/oro/assets.yml` files for the management-console was changed to follow the same standard as the configuration files for the OroCommerce storefront.
Use the `inputs` node instead of the group names.
```diff
- assets:
css:
-    'my_custom_asset_group':
+    inputs:
      - 'bundles/app/css/scss/first.scss'
      - 'bundles/app/css/scss/second.scss'
-    'another_asset_group':
      - 'bundles/app/css/scss/third.scss'
```
#### ApiBundle
* Fixed the `depends_on` configuration option of the `entities.fields` section of `Resources/config/oro/api.yml`. Now, only entity property names (or paths that contain entity property names) can be used in it. In addition, exception handling of invalid values for this option was improved to return more useful exception messages.
* By default processors for `customize_loaded_data` action are executed only for primary and included entities. Use `identifier_only: true` tag attribute if your processor should be executed for relationships.
* `finish_submit` event for `customize_form_data` action was renamed to `post_validate` and new `pre_validate` event was added.

#### LocaleBundle
* Removed loading data about currency code and currency symbols from bundle's file `./Resources/config/oro/currency_data.yml`. Now app gets this data from the Intl component by `IntlNumberFormatter`.
If you want to override some symbols, you can decorate `Oro\Bundle\LocaleBundle\Formatter\NumberFormatter::formatCurrency()` method.

#### MessageQueue Component
* In case when message processor specified in message not found this message will be rejected and exception will be thrown.

#### NotificationBundle
* Renamed the service `oro_notification.event_listener.email_notification_service` to `oro_notification.grid_helper`.
* Marked the following services as `private`: `oro_notification.entity_spool`, `oro_notification.form.subscriber.additional_emails`, `oro_notification.doctrine.event.listener`, `oro_notification.model.notification_settings`, `oro_notification.email_handler`, `oro_notification.mailer.spool_db`, `oro_notification.mailer.transport.eventdispatcher`, `oro_notification.mailer.transport`, `swiftmailer.mailer.db_spool_mailer`, `oro_notification.email_notification_entity_provider`, `oro_notification.form.subscriber.contact_information_emails`, `oro_notification.provider.email_address_with_context_preferred_language_provider`.

#### RequireJsBundle
* `oro_require_js.js_engine` configuration option was removed. Use `oro_asset.nodejs_path` instead.
#### SecurityBundle
* `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper::apply` method logic was changed to support Access rules.
* `oro_security.encoder.mcrypt` service was changed to `oro_security.encoder.default`.
#### TagBundle
* Changes in `/api/taxonomies` REST API resource:
    - the attribute `created` was renamed to `createdAt`
    - the attribute `updated` was renamed to `updatedAt`
    
#### UIBundle
* Changed all UI of backoffice
* Updated version of bootstrap from 2.3.0 to 4.1.1
* All global JS Views and Components are defined in the HTML through data attributes.
* Change target and name of a layout event. Now `layout` triggers `initLayout` event on DOM element instead `layoutInit` on `mediator`

### Removed

#### AsseticBundle
* Bundle was removed, use AssetBundle instead

#### DataAuditBundle
* The event `oro_audit.collect_audit_fields` was removed. Use decoration of `oro_dataaudit.converter.change_set_to_audit_fields` service instead.
* The alias `oro_dataaudit.listener.entity_listener` for the service `oro_dataaudit.listener.send_changed_entities_to_message_queue` was removed.

#### DatagridBundle
* Removed all logic related with column manager. The logic of column manager was transformed and expanded in [Datagrid Settings](https://github.com/oroinc/platform/blob/3.1/src/Oro/Bundle/DataGridBundle/Resources/doc/frontend/datagrid_settings.md)

#### EntityConfigBundle
* Removed `oro.entity_config.field.after_remove` event. Use `oro.entity_config.post_flush` event and `ConfigManager::getFieldConfigChangeSet('extend', $className, $fieldName)` method to check if a field was removed. If the change set has `is_deleted` attribute and its value is changed from `false` to `true` than a field was removed.

#### EntitySerializer Component
* Removed `excluded_fields` deprecated configuration attribute for an entity. Use `exclude` attribute for a field instead.
* Removed `result_name` deprecated configuration attribute for a field. Use `property_path` attribute instead.
* Removed `orderBy` deprecated configuration attribute. Use `order_by` attribute instead.
* Removed deprecated signature `function (array &$item) : void` of post serialization handler that can be specified in `post_serialize` configuration attribute. Use `function (array $item, array $context) : array` instead.

#### InstallerBundle
* Environment variable `ORO_PHP_PATH` is no longer supported for specifying path to PHP executable.

#### NotificationBundle
* Removed the following DIC parameters: `oro_notification.event_entity.class`, `oro_notification.emailnotification.entity.class`, `oro_notification.massnotification.entity.class`, `oro_notification.entity_spool.class`, `oro_notification.manager.class`, `oro_notification.email_handler.class`, `oro_notification.doctrine_listener.class`, `oro_notification.event_listener.mass_notification.class`, `oro_notification.form.type.email_notification.class`, `oro_notification.form.type.recipient_list.class`, `oro_notification.form.handler.email_notification.class`, `oro_notification.form.type.email_notification_entity_choice.class`, `oro_notification.email_notification.manager.api.class`, `oro_notification.mailer.transport.spool_db.class`, `oro_notification.mailer.transport.spool_entity.class`, `oro_notification.event_listener.email_notification_service.class`, `oro_notification.email_notification_entity_provider.class`, `oro_notification.mass_notification_sender.class`.

#### QueryDesignerBundle
* The unused alias `oro_query_designer.virtual_field_provider` for the service `oro_entity.virtual_field_provider.chain` was removed.

#### SecurityBundle
* Removed `oro_security.acl_helper.process_select.after` event, create [Access Rule](https://github.com/oroinc/platform/blob/3.1/src/Oro/Bundle/SecurityBundle/Resources/doc/access-rules.md) instead.
* Removed `Oro\Bundle\SecurityBundle\ORM\Walker\AclWalker`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclConditionInterface`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclCondition`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\JoinAclCondition`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\JoinAssociationCondition`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclConditionStorage`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\SubRequestAclConditionStorage` and `Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionalFactorBuilder` classes because now ACL restrictions applies with Access Rules by `Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker`.
* Removed `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper::applyAclToCriteria` method. Please use `apply` method with Doctrine Query or Query builder instead.

#### Testing Component
* The class `Oro\Component\Testing\Validator\AbstractConstraintValidatorTest` was removed. Use `Symfony\Component\Validator\Test\ConstraintValidatorTestCase` instead.

#### UIBundle
* Removed the `loadBeforeAction` and `addToReuse` static methods of `BaseController` in JS. Global Views and Components can now be defined in the HTML over data attributes, the same way as an ordinary [Page Component](https://github.com/oroinc/platform/blob/3.1/src/Oro/Bundle/UIBundle/Resources/doc/reference/page-component.md).



## 3.0.0 (2018-07-27)
[Show detailed list of changes](incompatibilities-3-0.md)

### Added
#### ApiBundle
* Added `direction` option for fields in the `actions` section to be able to specify if the request data and the the response data can contain a field. Possible values are `input-only`, `output-only` or `bidirectional`. The `bidirectional` is the default value.
* Added the following operators for ComparisonFilter: `*` (`exists`), `!*` (`neq_or_null`), `~` (`contains`), `!~` (`not_contains`), `^` (`starts_with`), `!^` (`not_starts_with`), `$` (`ends_with`), `!$` (`not_ends_with`). For details see [how_to.md](https://doc.oroinc.com/3.1/backend/api/how-to/#advanced-operators-for-string-filter).
* Added the `case_insensitive` and `value_transformer` options for ComparisonFilter. See [how_to.md](https://doc.oroinc.com/3.1/backend/api/how-to/#enable-case-insensitive-string-filter) for more details.
* Added a possibility to enable custom API. See [how_to.md](https://doc.oroinc.com/3.1/backend/api/how-to/#enable-custom-api) for more information.




### Changed
#### ApiBundle
* The `oro_api.get_config.add_owner_validator` service was renamed to `oro_organization.api.config.add_owner_validator`
* The `oro_api.request_type_provider` DIC tag was renamed to `oro.api.request_type_provider`
* The `oro_api.routing_options_resolver` DIC tag was renamed to `oro.api.routing_options_resolver`
* The `oro_api.api_doc_annotation_handler` DIC tag was renamed to `oro.api.api_doc_annotation_handler`
* The HTTP method depended routes and controllers were replaced with the more general ones. The following is the full list of changes:

    | Removed Route | Removed Controller | New Route | New Controller |
    | --- | --- | --- | --- |
    | oro_rest_api_get | OroApiBundle:RestApi:get | oro_rest_api_item | OroApiBundle:RestApi:item |
    | oro_rest_api_delete | OroApiBundle:RestApi:delete | oro_rest_api_item | OroApiBundle:RestApi:item |
    | oro_rest_api_patch | OroApiBundle:RestApi:patch | oro_rest_api_item | OroApiBundle:RestApi:item |
    | oro_rest_api_post | OroApiBundle:RestApi:post | oro_rest_api_list | OroApiBundle:RestApi:list |
    | oro_rest_api_cget | OroApiBundle:RestApi:cget | oro_rest_api_list | OroApiBundle:RestApi:list |
    | oro_rest_api_cdelete | OroApiBundle:RestApi:cdelete | oro_rest_api_list | OroApiBundle:RestApi:list |
    | oro_rest_api_get_subresource | OroApiBundle:RestApiSubresource:get | oro_rest_api_subresource | OroApiBundle:RestApi:subresource |
    | oro_rest_api_get_relationship | OroApiBundle:RestApiRelationship:get | oro_rest_api_relationship | OroApiBundle:RestApi:relationship |
    | oro_rest_api_patch_relationship | OroApiBundle:RestApiRelationship:patch | oro_rest_api_relationship | OroApiBundle:RestApi:relationship |
    | oro_rest_api_post_relationship | OroApiBundle:RestApiRelationship:post | oro_rest_api_relationship | OroApiBundle:RestApi:relationship |
    | oro_rest_api_delete_relationship | OroApiBundle:RestApiRelationship:delete | oro_rest_api_relationship | OroApiBundle:RestApi:relationship |

#### UIBundle
* Twig filter `oro_tag_filter` was renamed to `oro_html_strip_tags`. See [documentation](https://doc.oroinc.com/3.1/backend/bundles/platform/UIBundle/twig-filters/#oro-html-strip-tags).

#### UserBundle
* The `oro_rest_api_get_user_profile` route was removed; use the `oro_rest_api_user_profile` route instead.
* The `Oro\Bundle\UserBundle\Api\Routing\UserProfileRestRouteOptionsResolver` and the `Oro\Bundle\UserBundle\Api\ApiDoc\UserProfileRestRouteOptionsResolver` route option resolvers were removed in favor of [routing.yml](https://doc.oroinc.com/3.1/backend/api/how-to/#add-a-custom-route).


### Removed
#### ApiBundle
* Removed deprecated routes contain `_format` placeholder.
* Removed the deprecated `Oro\Bundle\ApiBundle\Processor\CustomizeLoadedDataContext` class
* Removed the deprecated `Oro\Bundle\ApiBundle\Model\EntityDescriptor` class

#### EntityConfigBundle
* Removed the deprecated `getDefaultTimeout` and `setDefaultTimeout` methods from the `Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor` class

#### ImportExportBundle
* Removed the `Oro\Bundle\ImportExportBundle\EventListener\ExportJoinListener` class and the corresponding `oro_importexport.event_listener.export_join_listener` service
* The `%oro_importexport.file.split_csv_file.size_of_batch%` parameter was removed; use `%oro_importexport.import.size_of_batch%` instead.

#### InstallerBundle
* Removed the deprecated `getDefaultTimeout` and `setDefaultTimeout` methods from the `Oro\Bundle\InstallerBundle\CommandExecutor` class

#### UIBundle
* Removed twig filter `oro_html_tag_trim`; use `oro_html_escape` instead. See [documentation](https://doc.oroinc.com/3.1/backend/bundles/platform/UIBundle/twig-filters/#oro-html-escape).
* Removed twig filter `oro_html_purify`; use `oro_html_strip_tags` instead. See [documentation](https://doc.oroinc.com/3.1/backend/bundles/platform/UIBundle/twig-filters/#oro-html-strip-tags).

#### WorkflowBundle
* Removed the `oro_workflow.cache.provider.workflow_definition` cache provider. Doctrine result cache is used instead.



## 2.6.0 (2018-01-31)
[Show detailed list of changes](incompatibilities-2-6.md)

### Added
#### ConfigBundle
* Added the configuration search provider functionality (see [documentation](https://github.com/oroinc/platform/blob/2.6/src/Oro/Bundle/ConfigBundle/Resources/doc/system_configuration.md#search-type-provider))
    * Service should be registered as a service with the `oro_config.configuration_search_provider` tag.
    * Class should implement `Oro\Bundle\ConfigBundle\Provider\SearchProviderInterface` interface.
    
#### EntityBundle
* Added the `oro_entity.structure.options` event (see [documentation](https://github.com/oroinc/platform/blob/2.6/src/Oro/Bundle/EntityBundle/Resources/doc/events.md#entity-structure-options-event))
* Added the `Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider`provider to retrieve data of entities structure (see [documentation](https://github.com/oroinc/platform/blob/2.6/src/Oro/Bundle/EntityBundle/Resources/doc/entity_structure_data_provider.md))
* Added JS `EntityModel`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/EntityBundle/Resources/public/js/app/models/entity-model.js) (see [documentation](https://github.com/oroinc/platform/blob/2.6/src/Oro/Bundle/EntityBundle/Resources/doc/client-side/entity-model.md))
* Added JS `EntityStructureDataProvider`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/EntityBundle/Resources/public/js/app/services/entity-structure-data-provider.js) (see [documentation](https://github.com/oroinc/platform/blob/2.6/src/Oro/Bundle/EntityBundle/Resources/doc/client-side/entity-structure-data-provider.md))
* Added `FieldChoiceView`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/EntityBundle/Resources/public/js/app/views/field-choice-view.js) Backbone view, as replacement for jQuery widget `oroentity.fieldChoice`.

#### EntityExtendBundle
* The `Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper::convertName` method was renamed to `convertEnumNameToCode`, visibility of this method was changed from `public` to `private` and it will throw an exception when the `iconv` function fails on converting the input string, instead of hashing the input string.

#### PlatformBundle

* Added a new DIC compiler pass `Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\ConsoleGlobalOptionsCompilerPass`
* Added the `oro_platform.console.global_options_provider` tag to be able to register the console command global options provider for `GlobalOptionsProviderRegistry`<sup>[[?]](./src/Oro/Bundle/PlatformBundle/Provider/Console/GlobalOptionsProviderRegistry.php "Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderRegistry")</sup> and it will be used in `GlobalOptionsListener`<sup>[[?]](./src/Oro/Bundle/PlatformBundle/EventListener/Console/GlobalOptionsListener.php "Oro\Bundle\PlatformBundle\EventListener\Console\GlobalOptionsListener")</sup>. This providers must implement `GlobalOptionsProviderInterface`<sup>[[?]](./src/Oro/Bundle/PlatformBundle/Provider/Console/GlobalOptionsProviderInterface.php "Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderInterface")</sup>.

#### QueryDesignerBundle
* Added `FunctionChoiceView`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/QueryDesignerBundle/Resources/public/js/app/views/function-choice-view.js) Backbone view, as replacement for jQuery widget `oroquerydesigner.functionChoice`.
#### SegmentBundle
* Added `SegmentChoiceView`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/SegmentBundle/Resources/public/js/app/views/segment-choice-view.js) Backbone view, as replacement for jQuery widget `orosegment.segmentChoice`.
#### UIBundle
* Added JS `Registry`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/UIBundle/Resources/public/js/app/services/registry/registry.js) (see [documentation](https://github.com/oroinc/platform/blob/2.6/src/Oro/Bundle/UIBundle/Resources/doc/reference/client-side/registry.md))


### Changed

#### ApiBundle
* The `build_query` group was removed from `update` and `delete` actions. From now the updating/deleting entity is loaded by `Oro\Bundle\ApiBundle\Processor\Shared\LoadEntity` processor instead of `Oro\Bundle\ApiBundle\Processor\Shared\LoadEntityByOrmQuery` processor.
* The priorities of some groups for the `update` action were changed. All changes are in the following table:

    | Group | Old Priority | New Priority |
    | --- | --- | --- |
    | load_data | -50 | -40 |
    | transform_data | -60 | -50 |
    | save_data | -70 | -60 |
    | normalize_data | -80 | -70 |
    | finalize | -90 | -80 |
    | normalize_result | -100 | -90 |

* The priorities of some groups for the `delete` action were changed. All changes are in the following table:

    | Group | Old Priority | New Priority |
    | --- | --- | --- |
    | load_data | -50 | -40 |
    | delete_data | -60 | -50 |
    | finalize | -70 | -60 |
    | normalize_result | -80 | -70 |

* Handling of `percent` data type in POST and PATCH requests was fixed. Before the fix, the percent value in GET and POST/PATCH requests was inconsistent; in POST/PATCH requests it was divided by 100, but GET request returned it as is. In this fix, the division by 100 was removed.
* For string filters the default value of the `allow_array` option was changed from `true` to `false`. This was done to allow filter data if a string field contains a comma.
#### DataGridBundle
* Parameter `count_hints` will have value of `hints` unless otherwise specified.
If other words from now
```yaml
datagrids:
    grid-name:
       ...
       source:
           ...
           hints:
               - SOME_QUERY_HINT
```
equivalent
```yaml
datagrids:
    grid-name:
       ...
       source:
           ...
           hints:
               - SOME_QUERY_HINT
           count_hints:
               - SOME_QUERY_HINT
```
#### SegmentBundle
* Refactored the `SegmentComponent` js-component to use `EntityStructureDataProvider`.
#### SidebarBundle
* In the `Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry` class, the return type in the `getWidgetDefinitions` and `getWidgetDefinitionsByPlacement` methods were changed from `ArrayCollection` to `array`.
#### UIBundle
* The `loadModules` method of the `'oroui/js/tools'` js-module now returns a promise object.
   * the element path is mostly turned off by default. It is turned on only in places where the status bar was enabled before. (edited) 

## 2.5.0 (2017-11-30)
[Show detailed list of changes](incompatibilities-2-5.md)

## 2.2.0 (2017-05-31)
[Show detailed list of changes](incompatibilities-2-2.md)

## 2.1.0 (2017-03-30)
[Show detailed list of changes](incompatibilities-2-1.md)

---
###### Upgrade-Toolkit coverage

[f] - Fully covered with `oro/upgrade-toolkit`

[p] - Partially covered with `oro/upgrade-toolkit`
