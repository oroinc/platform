<?php

namespace Oro\Component\Layout;

class BlockTypeChainRegistry
{
    /** @var ExtensionManagerInterface */
    protected $extensionManager;

    /** @var array */
    protected $chains = [];

    /**
     * @param ExtensionManagerInterface $extensionManager
     */
    public function __construct(ExtensionManagerInterface $extensionManager)
    {
        $this->extensionManager = $extensionManager;
    }

    /**
     * Returns the chain of all block types starting with the given block type.
     * The first element in the chain is the top type in the hierarchy, the last element is the given type.
     *
     * @param string|BlockTypeInterface $blockType The block type name or instance of BlockTypeInterface
     *
     * @return BlockTypeInterface[]
     */
    public function getBlockTypeChain($blockType)
    {
        if ($blockType instanceof BlockTypeInterface) {
            $name = $blockType->getName();
            $type = $blockType;
        } else {
            $name = $blockType;
            $type = null;
        }

        if (isset($this->chains[$name])) {
            return $this->chains[$name];
        }

        if (!$type) {
            $type = $this->extensionManager->getBlockType($name);
        }

        $chain      = [$type];
        $parentName = $type->getParent();
        while ($parentName) {
            $type = $this->extensionManager->getBlockType($parentName);
            array_unshift($chain, $type);
            $parentName = $type->getParent();
        }
        $this->chains[$name] = $chain;

        return $chain;
    }
}
