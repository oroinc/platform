<?php

namespace Oro\Bundle\EntityBundle\Provider;

class VirtualFieldProvider
{
    /** @var  EntityHierarchyProvider */
    protected $entityHierarchyProvider;

    /** @var array */
    protected $virtualFields;

    /**
     * Constructor
     *
     * @param EntityHierarchyProvider $entityHierarchyProvider
     * @param array                   $virtualFields
     */
    public function __construct(
        EntityHierarchyProvider $entityHierarchyProvider,
        $virtualFields
    ) {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->virtualFields           = $virtualFields;
    }

    /**
     * Return virtual fields by hierarchy
     *
     * e.g. OroSomeBundle:SomeEntity extends OroAddressBundle:AbstractAddress
     * and AbstractAddress has configured virtual field in entity.yml
     *
     * oro_entity:
     *   virtual_fields:
     *     Oro\Bundle\AddressBundle\Entity\AbstractAddress:
     *       country_virtual_field:
     *         query:
     *           select:
     *             expr: country.name
     *             return_type: string
     *           join:
     *             left:
     *               - { join: entity.country, alias: country }
     *
     * @return array
     */
    public function getVirtualFields()
    {
        $virtualFields = $this->virtualFields;

        $hierarchy = $this->entityHierarchyProvider->getHierarchy();
        foreach ($hierarchy as $hierarchyClassName => $hierarchyParents) {
            foreach ($virtualFields as $className => $fields) {
                if (in_array($className, $hierarchyParents)) {
                    if (!isset($virtualFields[$hierarchyClassName])) {
                        $virtualFields[$hierarchyClassName] = [];
                    }
                    $virtualFields[$hierarchyClassName] = array_merge(
                        $virtualFields[$hierarchyClassName],
                        $fields
                    );
                }
            }
        }

        return $virtualFields;
    }
}
