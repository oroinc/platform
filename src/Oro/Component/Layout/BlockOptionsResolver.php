<?php

namespace Oro\Component\Layout;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BlockOptionsResolver implements BlockOptionsResolverInterface
{
    /** @var BlockTypeRegistryInterface */
    protected $blockTypeRegistry;

    /** @var OptionsResolverInterface[] */
    protected $resolvers = [];

    /**
     * @param BlockTypeRegistryInterface $blockTypeRegistry
     */
    public function __construct(BlockTypeRegistryInterface $blockTypeRegistry)
    {
        $this->blockTypeRegistry = $blockTypeRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($blockType, array $options = [])
    {
        return $this->getOptionResolver($blockType)->resolve($options);
    }


    /**
     * @param string|BlockTypeInterface $blockType
     *
     * @return OptionsResolverInterface
     */
    protected function getOptionResolver($blockType)
    {
        if ($blockType instanceof BlockTypeInterface) {
            $name = $blockType->getName();
            $type = $blockType;
        } else {
            $name = $blockType;
            $type = null;
        }

        if (!isset($this->resolvers[$name])) {
            if (!$type) {
                $type = $this->blockTypeRegistry->getBlockType($name);
            }
            $parentName = $type->getParent();

            $optionsResolver = $parentName
                ? clone $this->getOptionResolver($parentName)
                : new OptionsResolver();

            $type->setDefaultOptions($optionsResolver);

            $this->resolvers[$name] = $optionsResolver;
        }

        return $this->resolvers[$name];
    }
}
