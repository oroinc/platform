Configuration Reference
=======================

Configuration Form Definition
-----------------------------

The configuration should be placed in `Resources/config/oro/system_configuration.yml` file in any bundle.
The root node should be `system_configuration`.

### Available nodes

- `groups`    - definition of field groups. More [details](#groups)
- `fields`    - definition of field (form type). More [details](#fields)
- `tree`      - definition of configuration form tree. More [details](#tree)
- `api_tree`  - definition of configuration items available through API. More [details](#api-tree)

### Groups

This node should be also declared under root node and contains array of available field groups with its properties
Group is abstract fields bag, view representation of group managed on template level of specific configuration template
and dependent on its position in tree.
This means that group could be rendered as fieldset or tab or like part of accordion list.

```yaml
system_configuration:
    groups:
        platform: #unique name
            title: 'Platform'             # title is required
            icon:  fa-hdd-o
            priority: 30                  # sort order
            description: some description # add description on the next line after group header
            tooltip: some tooltip         # add tooltip on the same line after group header
            page_reload: false            # if true, page will be reloaded after save if something changed in the group
```

Groups definitions will be replaced recursive from configs that will parse after original definition.
So way to override existed group title is just to redefine group with the same name and `title` value

```yaml
system_configuration:
    groups:
        platform:
            title: 'New title' # overridden title
```

To customize a group configuration form without implementing own form type, it is possible to use `configurator` option.
The configurator can be implemented as a static method or a service.
The signature of the configurator must be `function (FormBuilderInterface $builder, array $options)`.

To specify a configurator the following syntax should be used

- `ClassName::methodName` for a static method
- `@service_id::methodName` for a method in a service

Please note that a group configuration form can have several configurators and they can be specified in different bundles.

**Example**

```yaml
system_configuration:
    groups:
        # string syntax
        some_group:
            configurator: Acme\Bundle\DemoBundle\SettingsFormConfigurator::buildForm
        # array syntax
        some_group:
            configurator:
                - Acme\Bundle\DemoBundle\SettingsFormConfigurator::buildForm
                - '@acme.settings_form_configurator::buildForm'
```

```php
<?php

namespace Acme\Bundle\DemoBundle;

use Symfony\Component\Form\FormBuilderInterface;

class SettingsFormConfigurator
{
    public static function buildForm(FormBuilderInterface $builder, array $options)
    {
        // put your configuration code here
    }
}
```

To customize handling of a group configuration form, it is possible to use `handler` option.
The handler can be implemented as a static method or a service.
The signature of the handler must be `function (ConfigManager $manager, ConfigChangeSet $changeSet, Form $form)`.

To specify a handler the following syntax should be used

- `ClassName::methodName` for a static method
- `@service_id::methodName` for a method in a service

Please note that a group configuration form can have several handlers and they can be specified in different bundles.
All handlers are executed only if a group configuration form does not have validation errors
and after the changed configuration option are saved. See [ConfigHandler](../../Form/Handler/ConfigHandler.php) for details.

**Example**

```yaml
system_configuration:
    groups:
        # string syntax
        some_group:
            handler: Acme\Bundle\DemoBundle\SettingsFormHandler::handle
        # array syntax
        some_group:
            handler:
                - Acme\Bundle\DemoBundle\SettingsFormHandler::handle
                - '@acme.settings_form_handler::handle'
```

```php
<?php

namespace Acme\Bundle\DemoBundle;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class SettingsFormHandler
{
    public static function handle(ConfigManager $manager, ConfigChangeSet $changeSet)
    {
        // put your additional form handling code here
    }
}
```

### Fields

Field declaration have required property `type`.
* `data_type` - must be specified for all fields except `ui_only` ones
* `type` - refers to form type of which field should be created
* `search_type` - indicates how to search by field value, read more in the [Search Type Provider](#search-type-provider) section
* `tooltip` - show additional info about field
* `acl_resource` - determines acl resource to check permissions to change config field value(optional)
* `priority` - sort order for displaying(optional)
* `ui_only` - indicates whether a field is used only on UI and do not related to any variable (optional, defaults to false)
* `property_path` - overrides configuration key where field's value will be stored (by default field's name used as path)

Also `options` available property here, it's just a proxy to form type definition

**Example**

```yaml
system_configuration:
    fields:
        date_format:
            data_type: string
            type: text # can be any custom type
            search_type: text
            options:
               label: 'Date format'
               tooltip: 'Some additional information'
               resettable: false  # should "use default checkbox" be shown(optional, default: true)
               # here we can override any default option of the given form type
               # also here can be added field tooltips
            acl_resource: 'acl_resource_name'
            priority: 20
            page_reload: false # if true, page will be reloaded after save if field changed
```

#### Tree

Configuration form tree makes definition of nested form elements.
Tree name should be unique to prevent merge of content from another trees.
All nested elements of the group should be placed under "children" node.
Sort order can be set with "priority" property

**Example**

```yaml
system_configuration:
    tree:
        tree_name:
            group1:
                priority: 20
                children:
                    some_group2:
                        children:
                            some_group3:
                                - some_field
                                ...
                                - some_another_field
```

#### API Tree

The `api_tree` section is used to define which configuration option should be available
through API, e.g. REST API. Also it can be used to split the options
by some logical groups. Using the group name an API client can get only subset of the options.

Please note that

- An configuration option must be defined in the [fields](#fields) section and must have `data_type` attribute.
- Nested groups are allowed. The nesting level is not limited.

**Example**

```yaml
system_configuration:
    api_tree:
        look-and-feel:                                         # group name
            oro_entity_pagination.enabled: ~                   # configuration option
        outlook:                                               # group name
            contacts:                                          # nested group name
                oro_crm_pro_outlook.contacts_enabled: ~        # configuration option
                oro_crm_pro_outlook.contacts_sync_direction: ~
            tasks:
                oro_crm_pro_outlook.tasks_enabled: ~
```

#### Search Type Provider

You can add your own rules how system configuration search should work.
By default search works:
- for [group](#groups) titles, look at the [`GroupSearchProvider`](../../Provider/GroupSearchProvider.php)
- for [field](#fields) labels and tooltips, look at the [`FieldSearchProvider`](../../Provider/FieldSearchProvider.php)
- for [fields](#fields) with `search_type: text`, look at the [`FieldSearchProvider`](../../Provider/FieldSearchProvider.php)
- for [fields](#fields) with `search_type: choice`, look at the [`FieldSearchProvider`](../../Provider/FieldSearchProvider.php)

##### Defining a Search Provider

Create your own `DemoSearchProvider` that implements [`SearchProviderInterface`](../../Provider/SearchProviderInterface.php).

```php
<?php

namespace Acme\Bundle\DemoBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Provider\SearchProviderInterface;

class DemoSearchProvider implements SearchProviderInterface
{
    /** @var ConfigBag */
    private $configBag;

    /**
     * @param ConfigBag $configBag
     */
    public function __construct(ConfigBag $configBag)
    {
        // use config bag to obtain group or field configuration data
        $this->configBag = $configBag;
    }

    /**
     * Determines whether this provider is applicable for the given name
     */
    public function supports($name)
    {
        // example how the field can be determined
        return $this->configBag->getFieldsRoot($name) !== false;
    }

    /**
     * Returns configuration search data by given name
     */
    public function getData($name)
    {
        // example how to to filter by `search_type` 
        $field = $this->configBag->getFieldsRoot($name);
        if ($field['search_type'] === 'your_own_search_type') {
            // return your own search data for current field
        }

        return [];
    }
}
```

Register your search provider as a service in the DI container with the `oro_config.configuration_search_provider` tag:

```yaml
acme_demo.configuration_search_provider.demo:
    class: Acme\Bundle\DemoBundle\Provider\DemoSearchProvider
    public: false
    arguments:
        - '@oro_config.config_bag'
    tags:
        - { name: oro_config.configuration_search_provider, priority: 20 }
```
