<?php

namespace Oro\Component\Layout;

class BlockTypeHierarchyRegistry implements BlockTypeHelperInterface
{
    /** @var ExtensionManagerInterface */
    protected $extensionManager;

    /** @var array */
    protected $types = [];

    /** @var array */
    protected $names = [];

    /** @var array */
    protected $nameMap = [];

    /**
     * @param ExtensionManagerInterface $extensionManager
     */
    public function __construct(ExtensionManagerInterface $extensionManager)
    {
        $this->extensionManager = $extensionManager;
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
    public function getBlockTypeNames($blockType)
    {
        $name = $this->ensureInitialized($blockType);

        return $this->names[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockTypes($blockType)
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
                $type = $this->extensionManager->getBlockType($name);
            }

            $types      = [$type];
            $names      = [$type->getName()];
            $nameMap    = [$type->getName() => true];
            $parentName = $type->getParent();
            while ($parentName) {
                $type = $this->extensionManager->getBlockType($parentName);

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
