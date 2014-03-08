OroMigrationBundle
==================

Database structure and data manipulator. 

Database structure migrations
==================

Each bundle can have migration files that allow to update database schema.

Migration files should be located in Migrations\Schema\version_number folder. Version numbers must be an PHP-standardized version number string with some limitations. This string should not contain "." and "+" chars as version parts separator. More info about PHP-standardized version number string can be found in [PHP manual][1].

Each migration class must implement Oro\Bundle\MigrationBundle\Migration\Migration interface.

A migration class must implements `up` function. This function receive current database structure in `schema` variable and `queries` parameter witch can be used to add additional queries.

With schema parameter, you can create or update database structure without fear of compatibility between database engines. 
If developer want to add additional queries to the database before or after applying schema modification, he can used `queries` parameter. This parameter allow to add additional queries witch will be executed before (**addPreSql** function) or after (**addSql** or **addPostSql**  function). 

Example of migration file:

``` php
<?php

namespace Oro\Bundle\TestBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTestBundle extends Migration
{
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
        
        $queries->addSql(
            $queries->getRenameTableSql('old_table_name', 'new_table_name')
        );
        $queries->addSql(
            "ALTER TABLE another_table ADD COLUMN test_column INT NOT NULL",
        );
    }
}

``` 

 
Each bundle can have installation migration file. Install migration file replaces running multiple migration files. Install migration classes must implement Oro\Bundle\MigrationBundle\Migration\Installation interface. This files must return max migration version number that this installation file replace.

During install process, if install migration script was found, it will be loaded first and then will be loaded migration files with versions bigger then returned version from the installation migration file.

For example. We have v1_0, v1_1, v1_2, v1_3 migrations. And additionaly, we have install migration file. This class return v1_2 as migration version. So, during the installation will be loaded install migration file and then only v1_3 migration file. Migrations from v1_0 to v1_2 will not be loaded.

Exaple of installation migration file:

``` php
<?php

namespace Oro\Bundle\TestBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTestBundleInstaller extends Installation
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

To run migrations, there is **oro:migration:load** command. This command collect migration files from the bundles, sort by versions and apply sql queries.

This command support some additional options: 

 - **dry-run** - Outputs list of migrations without apply them;
 - **show-queries** - Outputs list of database queries for each migration file;
 - **bundles** - A list of bundles to load data from. If option is not set, migrations will be taken from all bundles;
 - **exclude** - A list of bundle names which migrations should be skipped.

To help in creation migration files. there is **oro:migration:dump** command. This command output current database structure in plain sql queries or with Doctrine\DBAL\Schema\Schema queries.

Data fixtures
==================

Syfony allow load data with data fixtures. But this fixtures will be run each time then doctrine:fixtures:load command was run.

To avoid loading the same fixture several time, was created **oro:migration:data:load** command. This command run each data fixture file only once.

This command support two types of migration files: main data fixtures and demo data fixtures. During installation, user can select to load or not demo data.

Data fixtures for this command should be put in Migrations/Data/ORM or in Migrations/Data/Demo/ORM directory.

Fixtures order can be changed with standart Doctrine ordering or dependency functionality. More information about fixture ordering can be found in [doctrine data fixtures manual][2].


  [1]: http://php.net/manual/en/function.version-compare.php
  [2]: https://github.com/doctrine/data-fixtures#fixture-ordering
