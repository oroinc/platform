<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * This class applies entity hierarchy onto declaration of virtual fields or relations.
 * For instance virtual fields configured for some abstract class will be added to all inherited classes.
 */
abstract class AbstractConfigVirtualProvider
{
    /** @var EntityHierarchyProviderInterface */
    private $entityHierarchyProvider;

    /** @var array */
    private $items;

    public function __construct(EntityHierarchyProviderInterface $entityHierarchyProvider)
    {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
    }

    /**
     * @return array
     */
    abstract protected function getConfiguration();

    /**
     * Gets initialized virtual items.
     *
     * When OroSomeBundle:SomeEntity extends OroAddressBundle:AbstractAddress and AbstractAddress has configured
     * virtual item all AbstractAddress virtual fields will be available in scope of OroSomeBundle:SomeEntity
     *
     * @return array
     */
    protected function getItems()
    {
        if ($this->items === null) {
            $items = $this->getConfiguration();

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

        return $this->items;
    }
}
