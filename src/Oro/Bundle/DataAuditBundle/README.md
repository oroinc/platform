# OroDataAuditBundle

OroDataAuditBundle enables tracking of entity fields changes history. The bundle supports the entity fields audit configuration on the code level and in the entity management UI.

* [Entity configuration](#entity-configuration)
    * [Example](#example-of-annotation-configuration)
* [Additional fields](#additional-fields)
* [User documentation](#user-documentation)

## Entity Configuration

DataAudit can only be enabled for Configurable entities. To add a property of an entity to the changelog,
you simply have to enable the audit for the entity itself and specify some fields you want to be logged.
To achieve this, you should use the `@Config` and `@ConfigField` annotations for the entity.

Audit can be enabled/disabled per an entire entity or for separate fields in UI `System->Entities->EntityManagement`
(attribute `Auditable`).

### Example of annotation configuration

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
 *              "icon"="fa-product" # default icon class which will be used
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

## Additional fields

In every entry of audit log you can store additional fields. There are no requirements to type of a data.
If object is passed to an array, it will be properly sanitized and converted to supported format.
To clarify the need of additional fields, let's use real example:

Developer creates an extension which integrates OroCRM with external system (eg. SystemA).
This integration synchronizes Product entities between systems.
However, identifier of Product entity is different in CRM - id and different in SystemA - system_id.
SystemA tracks changes in CRM calling API audit endpoint and match Products on its side by system_id, so
it will be really helpful to attach this field to every response (eg. when Product is removed).
To make it happen one can use "additional fields". Entity must implement `AuditAdditionalFieldsInterface`.
In our example it could looks like:

```php
<?php

namespace MyBundle\Entity;

use Oro\Bundle\DataAuditBundle\Entity\AuditAdditionalFieldsInterface;

class Product implements AuditAdditionalFieldsInterface
{
    // rest of code
    
    public function getAdditionalFields()
    {
        return ['system_id' => $this->getSystemId()];
    }
}
```

## User documentation

For full documentation visit http://www.oroinc.com/doc/orocrm/current/book/data-audits
