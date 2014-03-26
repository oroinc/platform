OroDataAuditBundle
==================

Bundle provides entity change log functionality using "Loggable" Doctrine extension.

## Usage ##

In your entity add special annotations to mark particular fields versioned.

``` php
<?php
// ...
use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;

/**
 * @ORM\Entity()
 * @ORM\Table(name="my_table")
 * @Oro\Loggable
 */
class MyEntity
{
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Oro\Versioned
     */
    protected $myField;

    /**
     * @var MyCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="MyCollectionItem")
     * @Oro\Versioned("getLabel") // "getLabel" it is a method which provides data for Loggable. If method doesn't set Loggable will use "__toString" for relation entity.
     */
    protected $myCollection;
}

That's it! `myField` and `$myCollection` becomes versioned and will be tracked by DataAudit bundle.
