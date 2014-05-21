<?php

namespace Oro\Bundle\EntityBundle\Provider;

class VirtualFieldProvider implements VirtualFieldProviderInterface
{
    /** @var  EntityHierarchyProvider */
    protected $entityHierarchyProvider;

    /** @var array */
    protected $configurationVirtualFields = [];

    /** @var  array */
    protected $virtualFields;

    /**
     * Constructor
     *
     * @param EntityHierarchyProvider $entityHierarchyProvider
     * @param array                   $configurationVirtualFields
     */
    public function __construct(
        EntityHierarchyProvider $entityHierarchyProvider,
        $configurationVirtualFields
    ) {
        $this->entityHierarchyProvider    = $entityHierarchyProvider;
        $this->configurationVirtualFields = $configurationVirtualFields;
    }

    /**
     * {@inheritDoc}
     */
    public function getVirtualFields($className)
    {
        $this->ensureVirtualFieldsInitialized();

        return isset($this->virtualFields[$className]) ? array_keys($this->virtualFields[$className]) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualField($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized();

        return isset($this->virtualFields[$className][$fieldName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFieldQuery($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized();

        return $this->virtualFields[$className][$fieldName]['query'];
    }

    /**
     * Ensure virtual fields are initialized
     *
     * e.g. OroSomeBundle:SomeEntity extends OroAddressBundle:AbstractAddress
     * and AbstractAddress has configured virtual field in entity_virtual_fields.yml
     *
     * oro_entity_virtual_fields:
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
        if ($this->virtualFields === null) {
            $virtualFields = $this->configurationVirtualFields;

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

            $this->virtualFields = $virtualFields;
        }
    }
}
