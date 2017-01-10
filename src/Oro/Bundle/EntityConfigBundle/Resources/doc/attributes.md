Attributes Configuration
=====================
Attributes - is logic which gives a possibility to assign dynamic fields to an entity. Each attribute in its essence is
config field which holds some value. Attributes have own CRUD and field types which is similar to extend fields.
All attributes are divided into certain families and groups. So it is possible apply manipulations such as assigning,
removing, etc., between these sets. 


Enabling attributes for an entity
---------------
By applying several modifications for extendable and configurable entity it is possible to activate attributes functionality. 
Lets assume we have some entity 'Product', to activate attributes for it is required: 

1. Add @Config annotation to Product class with 'attributes' scope and add key 'has_attributes' set to true.
2. Add field **attributeFamily** with many to one relation on **Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily**,
    make field configurable and activate import (if entity can be imported), add migration for field.
3. Implement **AttributeFamilyAwareInterface** and accessors for **attributeFamily** field

``` php
<?php
/**
 * @ORM\Entity
 * @Config(
 *  defaultValues={
 *      "attributes"={
 *          "has_attributes"=true
 *      }
 *  }
 * )
 */
class Product extends ExtendProduct implements AttributeFamilyAwareInterface
{
    /**
     * @var AttributeFamily
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily")
     * @ORM\JoinColumn(name="attribute_family_id", referencedColumnName="id", onDelete="RESTRICT")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=false
     *          },
     *          "importexport"={
     *              "order"=10
     *          }
     *      }
     *  )
     */
    protected $attributeFamily;
    
    /**
     * @param AttributeFamily $attributeFamily
     * @return $this
     */
    public function setAttributeFamily(AttributeFamily $attributeFamily)
    {
        $this->attributeFamily = $attributeFamily;

        return $this;
    }

    /**
     * @return AttributeFamily
     */
    public function getAttributeFamily()
    {
        return $this->attributeFamily;
    }
    
    ...
}
```

Don't forget to clear cache and update configs after changes.

Creating an attribute
--------------------
After configuring entity with attribute's scope, routes for creating and manipulating attributes became available -
'oro_attribute_index', 'oro_attribute_family_index', etc. Each route accepts one required parameter - 'alias'. Alias of 
your entity class should be passed to tell controller's action with which entity to work. If no existing alias 
passed or entity has not 'attributes' configured it will not be possible to access actions. You can add routes to navigation
to simplify access (product as an example):

```yml
    product_attributes_index:
        label: 'oro.product.menu.product_attributes'
        route: 'oro_attribute_index'
        route_parameters:
            alias: 'product'
        extras:
            routes: ['oro_attribute_*']
````

And add this item to needed place in navigation tree.

'oro_attribute_create' route is responsible for creating new attribute. To create it you have to pass two steps. On first
fill two required fields - attribute code (some unique slug representation) and type (string, bigint, select, etc). On 
second step 'label' field is only required. Depending on attribute type and options 'Filterable', 'Sortable' different
storage type may be applied to it before attribute saved. If one of the listed options will be set to true or not simple attribute 
type chosen (select, multiselect) 'table column' storage type will be predefined. To finally apply changes need to click on
'Update schema' button (it will physically crete field in table). While the rest of the circumstances 'serialized field'
storage type will be predefined for the attribute. There are two kinds of attributes custom (added via ui) and system 
(added via migration)

Attribute Families and Groups
---------------------
Point to understand, entity has no direct relation on attributes but **AttributeFamily** can be assigned to it. You can 
create new family for entity using route 'oro_attribute_family_create' with corresponding alias. In its turn **AttributeFamily**
holds collection of **AttributeGroup**. It is needed to create family at first, it can be done accessing route -
'oro_attribute_family_create'. 'Code' and 'Labels' fields are required, also at least on attribute group should be assigned.
Attribute groups can be created directly on family create/edit page by simply adding new group to collection. Each group
collection element has required field 'Label' and select with attributes previously created for entity (certain class).
You can assign any attribute for any group or move if it is already assigned, also it is possible to remove attribute
from group but not system (it can be moved only).

Attribute ACL
------------------------
Main idea of attributes is to add separate logic to manipulate extend entity fields which marked as attributes despite
access to Entity management can be denied for some roles.

 