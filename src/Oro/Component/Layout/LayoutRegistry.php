<?php

namespace Oro\Component\Layout;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Extension\ExtensionInterface;

class LayoutRegistry implements LayoutRegistryInterface
{
    /** @var array */
    private $extensions = [];

    /** @var ExtensionInterface[] */
    private $sorted;

    /** @var BlockTypeInterface[] */
    private $types = [];

    /** @var DataProviderInterface[] */
    private $dataProviders = [];

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        if (!is_string($name)) {
            throw new Exception\UnexpectedTypeException($name, 'string');
        }

        if (isset($this->types[$name])) {
            return $this->types[$name];
        }

        $type = null;
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasType($name)) {
                $type = $extension->getType($name);
                break;
            }
        }
        if (!$type) {
            throw new Exception\InvalidArgumentException(sprintf('Could not load a block type "%s".', $name));
        }
        $this->types[$name] = $type;

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions($name)
    {
        $extensions = [];
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasTypeExtensions($name)) {
                $extensions = array_merge($extensions, $extension->getTypeExtensions($name));
            }
        }

        return $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getContextConfigurators()
    {
        $configurators = [];
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasContextConfigurators()) {
                $configurators = array_merge($configurators, $extension->getContextConfigurators());
            }
        }

        return $configurators;
    }

    /**
     * {@inheritdoc}
     */
    public function findDataProvider($name)
    {
        if (!is_string($name)) {
            throw new Exception\UnexpectedTypeException($name, 'string');
        }

        if (isset($this->dataProviders[$name])) {
            return $this->dataProviders[$name];
        }

        $dataProvider = null;
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasDataProvider($name)) {
                $dataProvider = $extension->getDataProvider($name);
                break;
            }
        }
        $this->dataProviders[$name] = $dataProvider;

        return $dataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions($name, OptionsResolverInterface $resolver)
    {
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasTypeExtensions($name)) {
                $typeExtensions = $extension->getTypeExtensions($name);
                foreach ($typeExtensions as $typeExtension) {
                    $typeExtension->setDefaultOptions($resolver);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildBlock($name, BlockBuilderInterface $builder, array $options)
    {
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasTypeExtensions($name)) {
                $typeExtensions = $extension->getTypeExtensions($name);
                foreach ($typeExtensions as $typeExtension) {
                    $typeExtension->buildBlock($builder, $options);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView($name, BlockView $view, BlockInterface $block, array $options)
    {
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasTypeExtensions($name)) {
                $typeExtensions = $extension->getTypeExtensions($name);
                foreach ($typeExtensions as $typeExtension) {
                    $typeExtension->buildView($view, $block, $options);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView($name, BlockView $view, BlockInterface $block, array $options)
    {
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasTypeExtensions($name)) {
                $typeExtensions = $extension->getTypeExtensions($name);
                foreach ($typeExtensions as $typeExtension) {
                    $typeExtension->finishView($view, $block, $options);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateLayout($id, LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item)
    {
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasLayoutUpdates($item)) {
                $layoutUpdates = $extension->getLayoutUpdates($item);
                foreach ($layoutUpdates as $layoutUpdate) {
                    $layoutUpdate->updateLayout($layoutManipulator, $item);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasContextConfigurators()) {
                $configurators = $extension->getContextConfigurators();
                foreach ($configurators as $configurator) {
                    $configurator->configureContext($context);
                }
            }
        }
    }

    /**
     * Registers an layout extension.
     *
     * @param ExtensionInterface $extension
     * @param int                $priority
     *
     * @return self
     */
    public function addExtension(ExtensionInterface $extension, $priority = 0)
    {
        $this->extensions[$priority][] = $extension;
        $this->sorted                  = null;
    }

    /**
     * Returns all registered extensions sorted by priority.
     *
     * @return ExtensionInterface[]
     */
    protected function getExtensions()
    {
        if (null === $this->sorted) {
            ksort($this->extensions);
            $this->sorted = !empty($this->extensions)
                ? call_user_func_array('array_merge', $this->extensions)
                : [];
        }

        return $this->sorted;
    }
}
