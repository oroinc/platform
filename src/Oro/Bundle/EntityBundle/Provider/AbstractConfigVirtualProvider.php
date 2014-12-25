<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * This class applies entity hierarchy onto declaration of virtual fields or relations.
 * For instance virtual fields configured for some abstract class will be added to all inherited classes.
 */
class AbstractConfigVirtualProvider
{
    /**
     * @var array
     */
    protected $items;

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var EntityHierarchyProvider
     */
    protected $entityHierarchyProvider;

    /**
     * @param EntityHierarchyProvider $entityHierarchyProvider
     * @param array $configuration
     */
    public function __construct(EntityHierarchyProvider $entityHierarchyProvider, array $configuration)
    {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->configuration = $configuration;
    }

    /**
     * Ensure virtual items are initialized
     *
     * e.g. OroSomeBundle:SomeEntity extends OroAddressBundle:AbstractAddress
     * and AbstractAddress has configured virtual item
     *
     * virtual_fields:
     *   Oro\Bundle\AddressBundle\Entity\AbstractAddress:
     *     country_virtual_field:
     *       query:
     *         select:
     *           expr: country.name
     *           return_type: string
     *         join:
     *           left:
     *             - { join: entity.country, alias: country }
     *
     * So, result for OroSomeBundle:SomeEntity will be:
     * OroSomeBundle:SomeEntity:
     *      ...
     *      own virtual fields
     *      ...
     *      country_virtual_field
     */
    protected function ensureVirtualFieldsInitialized()
    {
        if ($this->items === null) {
            $items = $this->configuration;

            $hierarchy = $this->entityHierarchyProvider->getHierarchy();
            foreach ($hierarchy as $hierarchyClassName => $hierarchyParents) {
                foreach ($items as $className => $fields) {
                    if (in_array($className, $hierarchyParents)) {
                        if (!isset($items[$hierarchyClassName])) {
                            $items[$hierarchyClassName] = [];
                        }
                        $items[$hierarchyClassName] = array_merge(
                            $items[$hierarchyClassName],
                            $fields
                        );
                    }
                }
            }

            $this->items = $items;
        }
    }
}
