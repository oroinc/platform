<?php

namespace Oro\Component\Layout;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class BlockOptionsResolver
{
    /** @var LayoutRegistryInterface */
    protected $registry;

    /** @var OptionsResolverInterface[] */
    protected $resolvers = [];

    /**
     * @param LayoutRegistryInterface $registry
     */
    public function __construct(LayoutRegistryInterface $registry)
    {
        $this->registry = $registry;
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
        $options = $this->resolveValueBags($options);

        return $resolver->resolve($options);
    }

    /**
     * @param array $options
     * @return array
     */
    protected function resolveValueBags(array $options)
    {
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $options[$key] = $this->resolveValueBags($value);
            } elseif ($value instanceof OptionValueBag) {
                $options[$key] = $value->buildValue($this->getOptionsBuilder($value, $options));
            }
        }

        return $options;
    }

    /**
     * @param OptionValueBag $valueBag
     * @param array $options
     * @return OptionValueBuilderInterface
     */
    protected function getOptionsBuilder(OptionValueBag $valueBag, array $options)
    {
        $isArray = false;

        // guess builder type based on arguments
        $actions = $valueBag->all();
        if ($actions) {
            /** @var Action $action */
            $action = reset($actions);
            $arguments = $action->getArguments();
            if ($arguments) {
                $argument = reset($arguments);
                if (is_array($argument)) {
                    $isArray = true;
                }
            }
        }

        if ($isArray) {
            return new ArrayOptionValueBuilder();
        }

        $delimiter = ' ';
        if (isset($options['delimiter'])) {
            $delimiter = $options['delimiter'];
        }

        $allowTokenize = true;
        if (isset($options['allowTokenize'])) {
            $allowTokenize = $options['allowTokenize'];
        }

        return new StringOptionValueBuilder($delimiter, $allowTokenize);
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
                $type = $this->registry->getType($name);
            }
            $parentName = $type->getParent();

            $optionsResolver = $parentName
                ? clone $this->getOptionResolver($parentName)
                : new OptionsResolver();

            $type->setDefaultOptions($optionsResolver);
            $this->registry->setDefaultOptions($name, $optionsResolver);

            $this->resolvers[$name] = $optionsResolver;
        }

        return $this->resolvers[$name];
    }
}
