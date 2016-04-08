OroEntityExtendBundle
=====================

- Allows to add an additional fields into existing entities
- Allows to add an additional relations into existing entities
- Allows to add new entities

All additions can be done through UI or using [migration scripts](../MigrationBundle/README.md).

You can find additional information about the bundle's features in their dedicated sections:

 - [Custom form types and options](./Resources/doc/custom_form_type.md)
 - [Associations](./Resources/doc/associations.md)
 - [Creating API to Manage Associations](./Resources/doc/associations_api.md)


Manage entities through UI
--------------------------

To manage existing entities or create new ones through UI go to **System > Entities > Entity Management** page. On this page you can see a list of all entities, but please note that you can modify only entities marked as extendable. Check **IS EXTEND** column to see whether an entity can be modified or not. To create a new entity click **Create entity** button at the top right corner of the page, fill the form and click **Save And Close**. Next add necessary fields to your entity clicking **Create field** button. To add new field to existing entity go to a view page of this entity and click **Create field** button. When all changes are made do not forget to click **Update schema** button to apply your changes to a database.

Modify existing entity
----------------------
The existing entity can be extended using migration scripts. To create new extended field you can use `addColumn` method with a special options named `oro_options`. The following example shows it:

``` php
<?php

namespace OroCRM\Bundle\AccountBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class OroCRMAccountBundle implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('orocrm_account');
        $table->addColumn(
            'description',
            'text',
            [
                'oro_options' => [
                    'extend'   => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'merge'    => ['display' => true],
                ]
            ]
        );
    }
}
```
Please pay attention on `owner` attribute in `extend` scope. In this example we use `ExtendScope::OWNER_CUSTOM`, it means that Oro platform is fully responsible for render this field on edit and view pages, as well as grids. The default value of `owner` attribute is `ExtendScope::OWNER_SYSTEM`, and in this case you have to add such field in forms, views and grids manually.

Also you can use [OroOptions](Migration/OroOptions.php) class to build `oro_options`. It can be helpful in same cases, for example if you work with arrays. The following example shows how to use this class:

``` php
<?php

namespace Acme\Bundle\TestBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AcmeTestBundle implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $options = new OroOptions();

        // include Email entity in 'acme' group
        // please note that 'append' method adds new value in additional to existing values
        // so, if Email entity was already included in some other groups this information will not be lost
        $options->append('grouping', 'groups', 'acme');

        $table = $schema->getTable('oro_email');
        $table->addOption(OroOptions::KEY, $options);
    }
}
```

Add relation
------------
Creating relations is more complex task than creation of regular field. Oro Platform provides a special extension for [Migration bundle](../MigrationBundle/README.md#extensions-for-database-structure-migrations) named [ExtendExtension](Migration/Extension/ExtendExtension.php) to help you. To use this extension your migration should implement [ExtendExtensionAwareInterface](Migration/Extension/ExtendExtensionAwareInterface.php). The following example shows how to create many-to-one relation:

``` php
<?php

namespace OroCRM\Bundle\SalesBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCRMSalesBundle implements Migration, ExtendExtensionAwareInterface
{
    protected $extendExtension;

    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('orocrm_sales_lead');
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $table,
            'users',
            'oro_user',
            'username',
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM]
            ]
        );
    }
}
```

More examples you can find in [relations chapter](./Resources/doc/relations.md).

Add option set field
--------------------
The option set is a special type of a field which allows to choose one or more options from a predefined set of options. Oro Platform provides two different data types for these purposes:

 - `enum` (named `Select` on UI) - only one option can be selected
 - `multiEnum` (named `Multi-Select` on UI) - several options can be selected

The option sets are quite complex types, but to understand how they work you need to know that both `enum` and `multiEnum` types are based on [relations](http://docs.doctrine-project.org/en/latest/reference/association-mapping.html), the main difference between them is that `enum` type is based on [many-to-one relation](http://docs.doctrine-project.org/en/latest/reference/association-mapping.html#many-to-one-unidirectional) however `multiEnum` type is based on [many-to-many relation](http://docs.doctrine-project.org/en/latest/reference/association-mapping.html#many-to-many-unidirectional). To add option set field to some entity you can use [ExtendExtension](Migration/Extension/ExtendExtension.php). The following example shows how it can be done:

``` php
<?php

namespace OroCRM\Bundle\SalesBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCRMSalesBundle implements Migration, ExtendExtensionAwareInterface
{
    protected $extendExtension;

    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('orocrm_sales_lead');
        $this->extendExtension->addEnumField(
            $schema,
            $table,
            'source', // field name
            'lead_source', // enum code
            false, // only one option can be selected
            false, // an administrator can add new options and remove existing ones
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM]
            ]
        );
    }
}
```

Please pay attention on the enum code parameter. Each option set should have code and it should be unique system wide and it's length should be no more than 21 characters (due to dynamic name generation and prefix).
Same principle applied to field name, in case above - it should be less than 27 symbols, due to suffix _id will be applied (30-3).
To load a list of options you can use data fixtures, for example:

``` php
<?php

<?php

namespace OroCRM\Bundle\DemoDataBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class LoadLeadSourceData extends AbstractFixture
{
    /** @var array */
    protected $data = [
        'Website'     => true,
        'Direct Mail' => false
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $className = ExtendHelper::buildEnumValueClassName('lead_source');

        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository($className);

        $priority = 1;
        foreach ($this->data as $name => $isDefault) {
            $enumOption = $enumRepo->createEnumValue($name, $priority++, $isDefault);
            $manager->persist($enumOption);
        }

        $manager->flush();
    }
}
```

As you can see in this example we use `buildEnumValueClassName` function to convert the option set code to the class name of an entity responsible to store all options of this option set. It is important because such entities are generated automatically by Oro Platform and you should not use the class name directly.
Also there are other functions in [ExtendHelper](Tools/ExtendHelper.php) class which can be helpful when you work with option sets:

 - `buildEnumCode` - builds an option set code based on its name.
 - `generateEnumCode` - generates an option set code based on a field for which this option set is created.
 - `buildEnumValueId` - builds an option identifier based on the option name. The option identifier is 32 characters length string.
 - `buildEnumValueClassName` - builds the class name of an entity responsible to store all options of the option set by the option set code.
 - `getMultiEnumSnapshotFieldName` - builds the name of a field which is used to store snapshot of selected values for option sets that allows to select several options. We use this data to avoid GROUP BY clause.
 - `getEnumTranslationKey` - builds label names for option set related translations.

As it was mentioned above each option set has own table to store available options. But translations for all options of all option sets are stored in one table. You can find more details in [EnumValueTranslation](Entity/EnumValueTranslation.php) and [AbstractEnumValue](Entity/AbstractEnumValue.php). The `AbstractEnumValue` is a base class for all option set entities. The `EnumValueTranslation` is used to store translations.

If by some reasons you create system option sets and you have to render it manually the following components can be helpful:

 - [TWIG extension](Twig/EnumExtension.php) to sort and translate options. It can be used in the following way: `optionIds|sort_enum(enumCode)`, `optionId|trans_enum(enumCode)`.
 - Symfony form types which can be used to build forms contain option set fields: [EnumChoiceType](Form/Type/EnumChoiceType.php) and [EnumSelectType](Form/Type/EnumSelectType.php).
 - Grid filters: [EnumFilter](Filter/EnumFilter.php) and [MultiEnumFilter](Filter/MultiEnumFilter.php). Some help how to use these filters in `datagrid.yml` and how to configure datagrid formatters for option sets you can find in [ExtendColumnOptionsGuesser](Grid/ExtendColumnOptionsGuesser.php). Please take in account that this class pass the class name as the option set identifier, but you can use the enum code as well.

Create custom entity
--------------------
A custom entity is an entity which has no PHP class in any bundle. The definition of such entity is created automatically in Symfony cache. To create a custom entity you can use [ExtendExtension](Migration/Extension/ExtendExtension.php). The following example shows it:

``` php
<?php

namespace Acme\Bundle\TestBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AcmeTestBundle implements Migration, ExtendExtensionAwareInterface
{
    protected $extendExtension;

    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $this->extendExtension->createCustomEntityTable(
            $schema,
            'TestCustomEntity'
        );
        $table->addColumn(
            'name',
            'string',
            [
                'length' => 100,
                'oro_options' => [
                    'extend'  => ['owner' => ExtendScope::OWNER_CUSTOM],
                ]
            ]
        );
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $table,
            'users',
            'oro_user',
            'first_name'
        );
    }
}
```

Preparing entity extend configuration
-------------------------------------
The following command prepares extended entities configuration:

```bash
php app/console oro:entity-extend:update-config
```

Updating database schema for extended entities
----------------------------------------------
The following command updates a database schema for extended entities:

```bash
php app/console oro:entity-extend:update-schema
```

Warming up the cache
--------------------
To save entity extend configuration stored in the database to the application cache, the following command can be used:

```bash
php app/console oro:entity-extend:cache:warmup
```

Clearing up the cache
---------------------
The following command removes all data related to entity extend functionality from the application cache:

```bash
php app/console oro:entity-extend:cache:clear --no-warmup
```
To reload all cached data just run this command without `--no-warmup` option.

Custom form type and options
---------------------

To configure custom form type and options for extended field, read [Custom form type and options](Resources/doc/custom_form_type.md)

Validation for extended fields
---------------------
By default all extended fields are not validated. In general extended fields rendered as usual forms, same way as not extended,
but there's a way to define validation constraints for all extended fields by their type.
This is done through the configuration of oro_entity_extend.validation_loader:

```yaml
    oro_entity_extend.validation_loader:
        class: %oro_entity_extend.validation_loader.class%
        public: false
        arguments:
            - @oro_entity_config.provider.extend
            - @oro_entity_config.provider.form
        calls:
            -
                - addConstraints
                -
                    - integer
                    -
                        - NotNull: ~
                        - Regex:
                            pattern: "/^[\d+]*$/"
                            message: "This value should contain only numbers."

            - [addConstraints, ["boolean", [{ NotBlank: ~ }]]]
```

To pass constraints there are two ways:
- use compiler pass to add 'addConstraints' call with necessary constraint configuration
- directly call service

Pay attention to the fact that all constraints defined here applied to all extended fields with corresponding type.

Another point to keep in mind - integer fields should be rendered as text. Because html5 validation works only in case 
when form submitted directly by user, and platform use javascript to submit forms. 
Platform relates on jQuery validation, but due to the nature of input[type=number] - it's not possible to get it's raw value when it's not number.


Extend Fields View
---------------------

Before extend fields rendering in view page, event "oro.entity_extend_event.before_value_render" fired. 
There is possibility for customize field rendering using this event.

As example you can create Event Listener. Example:

    oro_entity_extend.listener.extend_field_value_render:
        class: %oro_entity_extend.listener.extend_field_value_render.class%
        arguments:
            - @oro_entity_config.config_manager
            - @router
            - @oro_entity_extend.extend.field_type_helper
            - @doctrine.orm.entity_manager
        tags:
            - { name: kernel.event_listener, event: oro.entity_extend_event.before_value_render, method: beforeValueRender }

Each event listener try to made decision how we need to show field value and if it know how value need to be shown, he use `$event->setFieldViewValue($viewData);` to change field view value. Example:

    $underlyingFieldType = $this->fieldTypeHelper->getUnderlyingType($type);
        if ($value && $underlyingFieldType == 'manyToOne') {
            $viewData = $this->getValueForManyToOne(
                $value,
                $this->extendProvider->getConfigById($event->getFieldConfigId())
            );

            $event->setFieldViewValue($viewData);
        }

In this code we: 


- check if value not null and field type is "manyToOne". 
- calculate field view value and set it using `$event->setFieldViewValue($viewData);` 

In variable `$viewData` can be simple string or array `[ 'link' => 'example.com', 'title' => 'some text representation']`. In case of string it will be formatted in twig template automatically based on field type. In case of array we show field with text equal to `'title'`. Also title will be escaped. If `'link'` option exists we show field as link with href equal to `'link'` option value.

Custom fields and entities in search
------------------------------------

During creation or editing custom entity or field, user can set parameter 'searchable'. If this parameter will be set to true, this custom entity or field will be indexed by search engine.

For string field type, user can set additional parameter `title_field`. If this parameter is set to true, value of this field will be included into the search result title.

For the custom entity, search alias will be the same as table name. For example, if user creates new entity with the name 'myentity', 
it's table name will be 'oro_ext_myentity', and this name will be set search entity alias.

During indexation, for entity field will be created search field in search index with the same name.
