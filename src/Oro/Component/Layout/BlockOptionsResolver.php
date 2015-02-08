<?php

namespace Oro\Component\Layout;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class BlockOptionsResolver
{
    /** @var ExtensionManagerInterface */
    protected $extensionManager;

    /** @var OptionsResolverInterface[] */
    protected $resolvers = [];

    /**
     * @param ExtensionManagerInterface $extensionManager
     */
    public function __construct(ExtensionManagerInterface $extensionManager)
    {
        $this->extensionManager = $extensionManager;
    }

    /**
     * Returns the combination of the default options for the given block type and the passed options
     *
     * @param string|BlockTypeInterface $blockType The block type name or instance of BlockTypeInterface
     * @param array                     $options   The options
     *
     * @return array A list of options and their values
     *
     * @throws InvalidOptionsException if any given option is not applicable to the given block type
     */
    public function resolveOptions($blockType, array $options = [])
    {
        $resolver = $this->getOptionResolver($blockType);

        return $resolver->resolve($options);
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
                $type = $this->extensionManager->getType($name);
            }
            $parentName = $type->getParent();

            $optionsResolver = $parentName
                ? clone $this->getOptionResolver($parentName)
                : new OptionsResolver();

            $type->setDefaultOptions($optionsResolver);
            $this->extensionManager->setDefaultOptions($name, $optionsResolver);

            $this->resolvers[$name] = $optionsResolver;
        }

        return $this->resolvers[$name];
    }
}
