<?php

namespace Oro\Component\Layout;

class BlockTypeHelper implements BlockTypeHelperInterface
{
    /** @var LayoutRegistryInterface */
    protected $registry;

    /** @var array */
    protected $types = [];

    /** @var array */
    protected $names = [];

    /** @var array */
    protected $nameMap = [];

    /**
     * @param LayoutRegistryInterface $registry
     */
    public function __construct(LayoutRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function isInstanceOf($blockType, $targetName)
    {
        $name = $this->ensureInitialized($blockType);

        return isset($this->nameMap[$name][$targetName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeNames($blockType)
    {
        $name = $this->ensureInitialized($blockType);

        return $this->names[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes($blockType)
    {
        $name = $this->ensureInitialized($blockType);

        return $this->types[$name];
    }

    /**
     * @param string|BlockTypeInterface $blockType The block type name or instance of BlockTypeInterface
     *
     * @return string The name of the given block type
     */
    protected function ensureInitialized($blockType)
    {
        if ($blockType instanceof BlockTypeInterface) {
            $name = $blockType->getName();
            $type = $blockType;
        } else {
            $name = $blockType;
            $type = null;
        }

        if (!isset($this->types[$name])) {
            if (!$type) {
                $type = $this->registry->getType($name);
            }

            $types      = [$type];
            $names      = [$type->getName()];
            $nameMap    = [$type->getName() => true];
            $parentName = $type->getParent();
            while ($parentName) {
                $type = $this->registry->getType($parentName);

                array_unshift($types, $type);
                array_unshift($names, $type->getName());
                $nameMap[$type->getName()] = true;

                $parentName = $type->getParent();
            }
            $this->types[$name]   = $types;
            $this->names[$name]   = $names;
            $this->nameMap[$name] = $nameMap;
        }

        return $name;
    }
}
