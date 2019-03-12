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
     * @var EntityHierarchyProviderInterface
     */
    protected $entityHierarchyProvider;

    /**
     * @param EntityHierarchyProviderInterface $entityHierarchyProvider
     * @param array                            $configuration
     */
    public function __construct(EntityHierarchyProviderInterface $entityHierarchyProvider, array $configuration)
    {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->configuration = $configuration;
    }

    /**
     * Ensure virtual items are initialized.
     *
     * When OroSomeBundle:SomeEntity extends OroAddressBundle:AbstractAddress and AbstractAddress has configured
     * virtual item all AbstractAddress virtual fields will be available in scope of OroSomeBundle:SomeEntity
     */
    protected function ensureVirtualFieldsInitialized()
    {
        if ($this->items === null) {
            $items = $this->configuration;

            $hierarchy = $this->entityHierarchyProvider->getHierarchy();
            foreach ($hierarchy as $className => $parentClasses) {
                $currentItems = [];
                $parentClasses = \array_reverse($parentClasses);
                foreach ($parentClasses as $parentClass) {
                    if (isset($items[$parentClass])) {
                        $currentItems[] = $items[$parentClass];
                    }
                }
                if ($currentItems) {
                    if (isset($items[$className])) {
                        $currentItems[] = $items[$className];
                    }
                    $items[$className] = \array_merge(...$currentItems);
                }
            }

            $this->items = $items;
        }
    }
}
