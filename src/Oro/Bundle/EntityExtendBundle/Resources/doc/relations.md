Entity Relationships
====================

This chapter shows how to create different kind of relationships between entities.

Table of Contents
-----------------
- [Limitations](#limitations)
- [Many-To-One, Unidirectional](#many-to-one-unidirectional)
- [Many-To-One, Bidirectional](#many-to-one-bidirectional)
- [Many-To-Many, Unidirectional](#many-to-many-unidirectional)
- [Many-To-Many, Unidirectional, Without Default Entity](#many-to-many-unidirectional-without-default-entity)
- [Many-To-Many, Bidirectional](#many-to-many-bidirectional)
- [Many-To-Many, Bidirectional, Without Default Entity](#many-to-many-bidirectional-without-default-entity)
- [One-To-Many, Bidirectional](#one-to-many-bidirectional)
- [One-To-Many, Bidirectional, Without Default Entity](#one-to-many-bidirectional-without-default-entity)

Limitations
-----------

A new relationship may be created between two entities when at least the entity on the _owning_ side of the relationship (the one that owns the foreign key in the database) is extendable. This rule enables creating a relationship for the following combinations of entities:

|                           | Extendable entity | Non-extendable entity                 |
| --------------------------|:-----------------:|:-------------------------------------:|
| **Extendable entity**     | Bidirectional and unidirectional many-to-many, <br>bidirectional and unidirectional many-to-one, <br>bidirectional one-to-many| Many-to-many and many-to-one <br>relationships, unidirectional only |
| **Non-extendable entity** | None              | None                                  |

**Note**: All custom/extend relationships of type `One-To-Many` and `Many-To-One` will have [cascade: detach](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/working-with-associations.html#transitive-persistence-cascade-operations) enabled by default.

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

class OroSalesBundle implements Migration, ExtendExtensionAwareInterface
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
            'acme_user_room', // inverse side table
            'room_name', // column name is used to show related entity
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

class OroSalesBundle implements Migration, ExtendExtensionAwareInterface
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
            'acme_user_room', // inverse side table
            'room_name', // column name is used to show related entity
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            'oro_user', // owning side table
            'room', // owning side field name
            'acme_user_room', // inverse side table
            'users', // inverse side field name
            ['user_name'], // column names are used to show a title of owning side entity
            ['user_description'], // column names are used to show detailed info about owning side entity
            ['user_name'], // Column names are used to show owning side entity in a grid
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

class OroSalesBundle implements Migration, ExtendExtensionAwareInterface
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
            'acme_user_room', // inverse side table
            ['room_name'], // column names are used to show a title of related entity
            ['room_description'], // column names are used to show detailed info about related entity
            ['room_name'], // Column names are used to show related entity in a grid
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

class OroSalesBundle implements Migration, ExtendExtensionAwareInterface
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
            'acme_user_room', // inverse side table
            ['room_name'], // column names are used to show a title of related entity
            ['room_description'], // column names are used to show detailed info about related entity
            ['room_name'], // Column names are used to show related entity in a grid
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

class OroSalesBundle implements Migration, ExtendExtensionAwareInterface
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
            'acme_user_room', // inverse side table
            ['room_name'], // column names are used to show a title of related entity
            ['room_description'], // column names are used to show detailed info about related entity
            ['room_name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        $this->extendExtension->addManyToManyInverseRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // inverse side table
            'users', // inverse side field name
            ['user_name'], // column names are used to show a title of owning side entity
            ['user_description'], // column names are used to show detailed info about owning side entity
            ['user_name'], // Column names are used to show owning side entity in a grid
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

class OroSalesBundle implements Migration, ExtendExtensionAwareInterface
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
            'acme_user_room', // inverse side table
            ['room_name'], // column names are used to show a title of related entity
            ['room_description'], // column names are used to show detailed info about related entity
            ['room_name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]]
        );
        $this->extendExtension->addManyToManyInverseRelation(
            $schema,
            'oro_user', // owning side table
            'rooms', // owning side field name
            'acme_user_room', // inverse side table
            'users', // inverse side field name
            ['user_name'], // column names are used to show a title of owning side entity
            ['user_description'], // column names are used to show detailed info about owning side entity
            ['user_name'], // Column names are used to show owning side entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }
}
```

One-To-Many, Bidirectional
--------------------------

According to the Doctrine [documentation](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional), 
the one-to-many relationship has to be implemented as bidirectional unless it uses an additional join-table. Oro implementation of the ExtendExtension defines association on the "many" side, so it implicates a bidirectional type of relationship.

``` php
<?php

namespace Acme\Bundle\AcmeBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSalesBundle implements Migration, ExtendExtensionAwareInterface
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
            'acme_user_room', // inverse side table
            ['room_name'], // column names are used to show a title of related entity
            ['room_description'], // column names are used to show detailed info about related entity
            ['room_name'], // Column names are used to show related entity in a grid
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

class OroSalesBundle implements Migration, ExtendExtensionAwareInterface
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
            'acme_user_room', // inverse side table
            ['room_name'], // column names are used to show a title of related entity
            ['room_description'], // column names are used to show detailed info about related entity
            ['room_name'], // Column names are used to show related entity in a grid
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]]
        );
    }
}
```
