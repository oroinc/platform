Relations
=========

This chapter shows how to create different kind of relationships between extendable entities.

Many-To-One, Unidirectional
---------------------------

``` php
<?php

namespace Acme\Bundle\AcmeBundle\Migrations\Schema\v1_0;

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
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_user', // owning side table
            'room', // owning side field name
            'acme_user_room', // target side table
            'name', // column name is used to show related entity
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }
}
```

Many-To-One, Bidirectional
--------------------------

``` php
<?php

namespace Acme\Bundle\AcmeBundle\Migrations\Schema\v1_0;

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
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_user', // owning side table
            'room', // owning side field name
            'acme_user_room', // target side table
            'name', // column name is used to show related entity
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            'oro_user', // owning side table
            'room', // owning side field name
            'acme_user_room', // target side table
            'users', // target side field name
            ['name'], // column names are used to show a title of related entity
            ['name'], // column names are used to show detailed info about related entity
            ['name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }
}
```

Many-To-Many, Unidirectional
----------------------------

``` php
<?php

namespace Acme\Bundle\AcmeBundle\Migrations\Schema\v1_0;

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
        $this->extendExtension->addManyToManyRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // target side table
            ['name'], // column names are used to show a title of related entity
            ['name'], // column names are used to show detailed info about related entity
            ['name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }
}
```

Many-To-Many, Unidirectional, Without Default Entity
----------------------------------------------------

``` php
<?php

namespace Acme\Bundle\AcmeBundle\Migrations\Schema\v1_0;

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
        $this->extendExtension->addManyToManyRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // target side table
            ['name'], // column names are used to show a title of related entity
            ['name'], // column names are used to show detailed info about related entity
            ['name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]]
        );
    }
}
```

Many-To-Many, Bidirectional
---------------------------

``` php
<?php

namespace Acme\Bundle\AcmeBundle\Migrations\Schema\v1_0;

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
        $this->extendExtension->addManyToManyRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // target side table
            ['name'], // column names are used to show a title of related entity
            ['name'], // column names are used to show detailed info about related entity
            ['name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        $this->extendExtension->addManyToManyInverseRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // target side table
            'users', // target side field name
            ['name'], // column names are used to show a title of related entity
            ['name'], // column names are used to show detailed info about related entity
            ['name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }
}
```

Many-To-Many, Bidirectional, Without Default Entity
---------------------------------------------------

``` php
<?php

namespace Acme\Bundle\AcmeBundle\Migrations\Schema\v1_0;

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
        $this->extendExtension->addManyToManyRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // target side table
            ['name'], // column names are used to show a title of related entity
            ['name'], // column names are used to show detailed info about related entity
            ['name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]]
        );
        $this->extendExtension->addManyToManyInverseRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // target side table
            'users', // target side field name
            ['name'], // column names are used to show a title of related entity
            ['name'], // column names are used to show detailed info about related entity
            ['name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }
}
```

One-To-Many, Unidirectional
---------------------------

``` php
<?php

namespace Acme\Bundle\AcmeBundle\Migrations\Schema\v1_0;

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
        $this->extendExtension->addOneToManyRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // target side table
            ['name'], // column names are used to show a title of related entity
            ['name'], // column names are used to show detailed info about related entity
            ['name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }
}
```

One-To-Many, Unidirectional, Without Default Entity
---------------------------------------------------

``` php
<?php

namespace Acme\Bundle\AcmeBundle\Migrations\Schema\v1_0;

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
        $this->extendExtension->addOneToManyRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // target side table
            ['name'], // column names are used to show a title of related entity
            ['name'], // column names are used to show detailed info about related entity
            ['name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]]
        );
    }
}
```

One-To-Many, Bidirectional
--------------------------

``` php
<?php

namespace Acme\Bundle\AcmeBundle\Migrations\Schema\v1_0;

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
        $this->extendExtension->addOneToManyRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // target side table
            ['name'], // column names are used to show a title of related entity
            ['name'], // column names are used to show detailed info about related entity
            ['name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        $this->extendExtension->addOneToManyInverseRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // target side table
            'user', // target side field name
            'name', // column name is used to show related entity
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }
}
```

One-To-Many, Bidirectional, Without Default Entity
--------------------------------------------------

``` php
<?php

namespace Acme\Bundle\AcmeBundle\Migrations\Schema\v1_0;

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
        $this->extendExtension->addOneToManyRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // target side table
            ['name'], // column names are used to show a title of related entity
            ['name'], // column names are used to show detailed info about related entity
            ['name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]]
        );
        $this->extendExtension->addOneToManyInverseRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // target side table
            'user', // target side field name
            'name', // column name is used to show related entity
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }
}
```
