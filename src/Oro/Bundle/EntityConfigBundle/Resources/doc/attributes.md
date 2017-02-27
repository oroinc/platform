Attributes Configuration
========================

Attributes allow you dynamically create additional entity fields. An attribute is a configuration field with assigned value. Every attribute has a dedicated CRUD and field types, similarly to the extend fields. For easier management, attributes may be grouped and nested into attribute families.

Enabling Attributes for an Entity
---------------------------------

You can enable attributes for any extendable and configurable entity by doing the following: 

1. Add @Config annotation to the class with 'attributes' scope and add key 'has_attributes' set to true.
2. Add **attributeFamily** field with many to one relation on **Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily**. Make field configurable, activate import if necessary, and add migration.
3. Implement **AttributeFamilyAwareInterface** and accessors for **attributeFamily** field.

The following example illustrates enabling attributes for the *Product* entity:

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

**Note:** Remember to clear cache and update configuration after these changes.

Creating an Attribute
---------------------

After enabling attributes for an entity, you can use routes - *oro_attribute_index*, *oro_attribute_family_index*, etc - to create and manipulate the attributes. Alias of your entity class should be passed in the route parameters to help controller's action identify the necessary entity. The action is not accessible when the alias is missing or invalid, and when no 'attributes' are configured for the provided entity. 
You can add routes to the navigation tree to simplify access, like in the following example:

```yml
    product_attributes_index:
        label: 'oro.product.menu.product_attributes'
        route: 'oro_attribute_index'
        route_parameters:
            alias: 'product'
        extras:
            routes: ['oro_attribute_*']
```

The 'oro_attribute_create' route is responsible for creating new attribute. Attribute creation is split into two steps. On the first step, user provides the attribute code that is used as unique slug representation and attribute type (string, bigint, select, etc) that defines the data that should be captured on the following step. On the second step, user provides a label that is used to display attribute on the website (e.g. OroCommerce Web Store) and any other information that should be captured about the attribute. Oro aplication may store the attribute as *serialized field* or as a *table column*. The type of storage is selected based on the attribute type (simple types vs Select and Multi-Select) as well as setting of the *Filterable* and *Sortable* options. The product attribute storage type is set to *table column* for the attribute with Select of Multi-Select data type, and also for attribute of any type with Filterable or Sortable option enabled. This data type requires reindex that is launched by the user when they click  
*Update schema* in the All Product Attributes page. This triggers the field to be physically create in the table.

**Note**: Attributes created by user are labeled as custom, while attributes created during migrations are labeled as system. For system attributes deleting is disabled.

Attribute Families and Groups
-----------------------------

Entity has no direct relation to the attribute. Attributes are bound to the entity using the *AttributeFamily*. You can 
create a new attribute family for entity using the *oro_attribute_family_create* route with the corresponding alias. The *AttributeFamily* contains a collection of *AttributeGroups*. *AttributeFamily* requires *Code* and *Labels* values to be provided and must contain at least on attribute group. Attribute groups can be created directly on family create/edit page by simply adding new group to collection. Each group (a collection element) has required field 'Label' and a select control that allows picking one or manu attributes that were previously created for the entity (in a certain class). Attributes may be added to the group, moved from one group to another, and deleted from the group (except the system attributes that are moved to the default group upon deletion).

Attribute ACL
-------------
Attributes porvide supplementary logics that helps extend entity fields marked as attributes despite the 
limited access to the entity management.
