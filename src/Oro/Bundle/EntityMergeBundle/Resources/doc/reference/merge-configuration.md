# Configuration

### Table of Contents

- [Getting Started](./getting-started.md)
	- [What is Entity Merge](./getting-started.md#what-is-entity-merge "What is Entity Merge")
	- [Main Entities](./getting-started.md#main-entities)
	- [How it works](./getting-started.md#how-it-works)
- [Classes Diagram](./classes-diagram.md)
- Configuration
	- [Entity configuration](#entity-configuration)
	- [Mass action configuration](#mass-action-configuration)
	- [Other configurations](#other-configurations)

## Entity configuration

Entity can be configure on entity level and on fields level

Entity level configuration

```
entity_config:
    # Scope of entity merge
    merge:
        # Attributes applicable on entity level
        entity:
            items:
                # Options for rendering entity as string in the UI.
                # If these options are empty __toString will be used (if it's available).
                #
                # Method of entity to cast object to string
                cast_method: ~
                # Twig template to render object as string
                template: ~
                # Enable merge for this entity
                enable: ~
                # Max entities to merge
                max_entities_count: 5
```

Example:

     * @Config(
     *  ....
     *  defaultValues={
     *  ...
     *  "merge"={
     *  "enable"=true,
     *  "max_entities_count"=5
     *  }
     *  }
     * )

Fields Level configuration

```
entity_config:
    # Scope of entity merge
    merge:
        # Attributes applicable on entity fields level
        field:
            items:
                # Label of field that should be displayed for this field in merge UI, value will be translated
                label: ~
                # Display merge form for this field
                display: ~
                # Make field read-only in merge
                readonly: ~
                # Mode of merge supports next values, value can be an array or single mode:
                #   replace - replace value with selected one
                #   unite   - merge all values into one (applicable for collections and lists)
                merge_modes: ~
                # Flag for collection fields. This fields will support unite mode by default
                is_collection: ~
                # Options for rendering field value in the UI
                #
                # Method will be used to cast value to string (applicable only for values that are objects)
                cast_method: ~
                # Template can be used to render value of field
                template: ~
                # Method for setting value to entity
                setter: ~
                # Method for getting value to entity
                getter: ~
                # Can be used if you want to be see merge form for this field for entity on other side of relation,
                # For example there is a Call entity with field referenced to Account using ManyToOne unidirectional relation.
                # As Account doesn't have access to collection of calls the only possible place to configure calls merging
                # for account is this field in Call entity
                inverse_display: ~
                # Same as merge_modes but used for relation entity
                inverse_merge_modes: ~
                # Same as label but used for relation entity
                inverse_label: ~
                # Same as cast_method but used for relation entity
                inverse_cast_method: ~
                # Localization number type.
                # Default localisation handler support:
                # decimal, currency, percent, default_style, scientific, ordinal, duration, spellout
                render_number_style: ~
                # Type of date formatting, one of the format type constants. Possible values:
                # NONE
                # FULL
                # LONG
                # MEDIUM
                # SHORT
                render_date_type: ~
                # Type of time formatting, one of the format type constants. Possible values:
                # NONE
                # FULL
                # LONG
                # MEDIUM
                # SHORT
                render_time_type: ~
                # Date Time pattern
                # Example m/d/Y
                render_datetime_pattern: ~
                # Control escaping of the value when rendered in Merge table.
                # Use 'false' to disable escaping for the field (i.e. RichText) or set a Twig 'escape' method to enable:
                # 'html' (or true), 'html_attr', 'css', 'js', 'url'
                autoescape: true
```

Example:

```
class Account
{
     ...

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @ConfigField(defaultValues={"merge"={"enable"=true}})
     */
    protected $name;
```


## Mass action configuration

Example of merge mass action:

```
datagrids:
    accounts-grid:
        mass_actions:
            merge:
                type: merge
                entity_name: %orocrm_account.account.entity.class%
                data_identifier: a.id
```


## Other configurations

You can define your own "Strategy", "Steps", "Accessor" in DI by using tags with names "oro_entity_merge.strategy",
"oro_entity_merge.step", "oro_entity_merge.accessor".

Tagging merge strategy:

```
services:
    oro_entity_merge.strategy.replace:
        class: %oro_entity_merge.strategy.replace.class%
        arguments:
            - @oro_entity_merge.accessor.delegate
        tags:
            - { name: oro_entity_merge.strategy, priority: 100 }
```
You can define `priority` for strategy for being able to define your own merge strategy which will work with existing merge modes.
Supported merge strategy with greatest priority will be used for merge.

Tagging merge step:

```
services:
    oro_entity_merge.step.validate:
        class: %oro_entity_merge.step.validate.class%
        arguments:
            - @validator
        tags:
            - { name: oro_entity_merge.step }
```

Tagging accessor:

```
services:
	oro_entity_merge.accessor.inverse_association:
        class: %oro_entity_merge.accessor.inverse_association.class%
        arguments:
            - @oro_entity_merge.doctrine_helper
        tags:
            - { name: oro_entity_merge.accessor }
```
