OroEntityExtendBundle
=====================

- Allows to add an additional fields into existing entities
- Allows to add an additional relations into existing entities
- Allows to add new entities

All additions can be done through UI or using [migration scripts](../MigrationBundle/README.md).

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
                    'datagrid' => ['is_visible' => false],
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
        $extendExtension->addManyToOneRelation(
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

Add option set field
--------------------
The option set is a special type of a field which allows to choose one or more options from a predefined set of options. Oro Platform provides two different data types for these purposes:

 - `enum` (named `Select` on UI) - only one option can be selected
 - `multiEnum` (named `Multi-Select` on UI) - several options can be selected

The option sets are quite complex types, but to understand how they work you need to know that both `enum` and `multiEnum` types are based on [relations](http://docs.doctrine-project.org/en/2.0.x/reference/association-mapping.html), the main difference between them is that `enum` type is based on [many-to-one relation](http://docs.doctrine-project.org/en/2.0.x/reference/association-mapping.html#many-to-one-unidirectional) however `multiEnum` type is based on [many-to-many relation](http://docs.doctrine-project.org/en/2.0.x/reference/association-mapping.html#many-to-many-unidirectional). To add option set field to some entity you can use [ExtendExtension](Migration/Extension/ExtendExtension.php). The following example shows how it can be done:

``` php
<?php

namespace OroCRM\Bundle\SalesBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
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
        $extendExtension->addEnumField(
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

Please pay attention on the enum code parameter. Each option set should have code and it should be unique system wide.
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
