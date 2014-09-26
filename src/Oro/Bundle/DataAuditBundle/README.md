OroDataAuditBundle
==================

The OroDataAuditBundle provides changelogs for your entities that are configurable on an entity and in the GUI. 

**Please note**, *`Loggable` and `Versioned` annotations are not currently supported*.

###Entity Configuration

DataAudit can only be enabled for Configurable entities. To add a property of an entity to the changelog,
you simply have to enable the audit for the entity itself and specify some fields you want to be logged.
To achieve this, you should use the `@Config` and `@ConfigField` annotations for the entity.

Audit can be enabled/disabled per an entire entity or for separate fields in UI `System->Entities->EntityManagement`
(attribute `Auditable`).

###Example of annotation configuration
```php
// src/Acme/DemoBundle/Entity/Product.php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @Config( # entity default configuration
 *      routeName="acme_product_index", # optional, used to represent entity instances count as link
 *                                      # in EntityManagement UI
 *      routeView="acme_product_view",  # optional
 *      defaultValues={
 *          "entity"={ # entity configuration scope 'entity'
 *              "icon"="icon-product" # default icon class which will be used
 *                                    # can be changed via UI
 *          },
 *          "dataaudit"={ # entity configuration scope 'dataaudit'
 *              "auditable"=true # will enable dataaudit for this entity
 *                               # if not specified will be false
 *                               # but you will be able to enable audit via UI
 *          },
 *          # ...
 *          # any other entity scope default configuration
 *          # ...
 *      }
 * )
 */
class Product
{
    /**
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @ORM\Column(type="string")
     * @ConfigField( # field default configuration
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          # ...
     *          # any other entity scope default configuration
     *          # ...
     *      }
     * )
     */
    private $price;
}
```

For full documentation visit http://www.orocrm.com/documentation/index/current/book/data-audits
