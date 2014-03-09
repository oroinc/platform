OroMigrationBundle
==================

Database structure and data manipulator. 

Database structure migrations
-----------------------------

Each bundle can have migration files that allow to update database schema.

Migration files should be located in `Migrations\Schema\version_number` folder. A version number must be an PHP-standardized version number string, but with some limitations. This string must not contain "." and "+" characters as a version parts separator. More info about PHP-standardized version number string can be found in [PHP manual][1].

Each migration class must extend `Oro\Bundle\MigrationBundle\Migration\Migration` abstract class and must implement `up` method. This method receives a current database structure in `schema` parameter and `queries` parameter witch can be used to add additional queries.

With `schema` parameter, you can create or update database structure without fear of compatibility between database engines. 
If you want to execute additional SQL queries before or after applying a schema modification, you can use `queries` parameter. This parameter allows to add additional queries witch will be executed before (`addPreQuery` method) or after (`addQuery` or `addPostQuery` method).

Example of migration file:

``` php
<?php

namespace Acme\Bundle\TestBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;

class AcmeTestBundle implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('test_table');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('field', 'string', ['length' => 500]);
        $table->addColumn('another_field', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'old_table_name',
            'new_table_name'
        );
        $queries->addQuery(
            "ALTER TABLE another_table ADD COLUMN test_column INT NOT NULL",
        );
    }
}

``` 

 
Each bundle can have an **installation** file as well. This migration file replaces running multiple migration files. Install migration class must extend `Oro\Bundle\MigrationBundle\Migration\Installation` abstract class and must implement `up` and `getMigrationVersion` methods. The `getMigrationVersion` method must return max migration version number that this installation file replaces.

During an install process (it means that you installs a system from a scratch), if install migration file was found, it will be loaded first and then migration files with versions greater then a version returned by `getMigrationVersion` method will be loaded.

For example. We have `v1_0`, `v1_1`, `v1_2`, `v1_3` migrations. And additionally, we have install migration class. This class returns `v1_2` as a migration version. So, during an install process the install migration file will be loaded and then only `v1_3` migration file will be loaded. Migrations from `v1_0` to `v1_2` will not be loaded.

Example of install migration file:

``` php
<?php

namespace Acme\Bundle\TestBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AcmeTestBundleInstaller implements Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('test_installation_table');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('field', 'string', ['length' => 500]);
        $table->setPrimaryKey(['id']);
    }
}

``` 

To run migrations, there is **oro:migration:load** command. This command collects migration files from bundles, sorts them by version number and applies changes.

This command supports some additional options: 

 - **dry-run** - Outputs list of migrations without apply them;
 - **show-queries** - Outputs list of database queries for each migration file;
 - **bundles** - A list of bundles to load data from. If option is not set, migrations will be taken from all bundles;
 - **exclude** - A list of bundle names which migrations should be skipped.

Also there is **oro:migration:dump** command to help in creation migration files. This command outputs current database structure as a plain sql or as `Doctrine\DBAL\Schema\Schema` queries.

Extensions for database structure migrations
--------------------------------------------
Sometime you cannot use standard Doctrime methods for database structure modification. For example `Schema::renameTable` does not work because it drops existing table and then creates a new table. To help you to manage such case and allow to add some useful functionality to any migration a extensions mechanism was designed. The following example shows how `RenameExtension` can be used:
``` php
<?php

namespace Acme\Bundle\TestBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;

class AcmeTestBundle implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'old_table_name',
            'new_table_name'
        );
    }
}
```
As you can see to use the `RenameExtension` your migration class should implement `RenameExtensionAwareInterface` and `setRenameExtension` method.
Also there is some additional useful interfaces you can use in your migration class:

 - `ContainerAwareInterface` - provides an access to Symfony dependency container
 - `DatabasePlatformAwareInterface` - allows to write a database type independent migrations
 - `NameGeneratorAwareInterface` - provides an access to `DbIdentifierNameGenerator` class which can be used to generate names of indices, foreign key constraints and others.

Create own extensions for database structure migrations
-------------------------------------------------------
To create your own extension you need too do the following simple steps:

 - Create an extension class in `YourBundle/Migration/Extension` directory. Using `YourBundle/Migration/Extension` directory is not mandatory, but highly recommended. For example:  
``` php
<?php

namespace Acme\Bundle\TestBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MyExtension
{
    public function doSomething(Schema $schema, QueryBag $queries, /* other parameters, for example */ $tableName)
    {
        $table = $schema->getTable($tableName); // highly recommended to make sure that a table exists
        $query = 'SOME SQL'; /* or $query = new SqlMigrationQuery('SOME SQL'); */

        $queries->addQuery($query);
    }
}
```
 - Create `*AwareInterface` in the same namespase. It is important that the interface name should be `{ExtensionClass}AwareInterface` and set method should be `set{ExtensionClass}({ExtensionClass} ${extensionName})`. For example:
``` php
<?php

namespace Acme\Bundle\TestBundle\Migration\Extension;

/**
 * MyExtensionAwareInterface should be implemented by migrations that depends on a MyExtension.
 */
interface RenameExtensionAwareInterface
{
    /**
     * Sets the MyExtension
     *
     * @param MyExtension $myExtension
     */
    public function setMyExtension(MyExtension $myExtension);
}
```
 - Register an extension in dependency container. For example
``` yaml
parameters:
    acme_test.migration.extension.my.class: Acme\Bundle\TestBundle\Migration\Extension\MyExtension

services:
    acme_test.migration.extension.my:
        class: %acme_test.migration.extension.my.class%
        tags:
            - { name: oro_migration.extension, extension_name: test /*, priority: -10 - priority attribute is optional an can be helpful if you need to override existing extension */ }
```

If you need an access to the database platform or the name generator you extension class should implement `DatabasePlatformAwareInterface` or `NameGeneratorAwareInterface` appropriately.  
 
Data fixtures
-------------

Syfony allows to load data using data fixtures. But these fixtures are run each time when `doctrine:fixtures:load` command is executed.

To avoid loading the same fixture several time, **oro:migration:data:load** command was created. This command guarantees that each data fixture will be loaded only once.

This command supports two types of migration files: `main` data fixtures and `demo` data fixtures. During an installation, user can select to load or not demo data.

Data fixtures for this command should be put in `Migrations/Data/ORM` or in `Migrations/Data/Demo/ORM` directory.

Fixtures order can be changed with standard Doctrine ordering or dependency functionality. More information about fixture ordering can be found in [doctrine data fixtures manual][2].


  [1]: http://php.net/manual/en/function.version-compare.php
  [2]: https://github.com/doctrine/data-fixtures#fixture-ordering
