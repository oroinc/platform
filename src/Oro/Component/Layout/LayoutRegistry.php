<?php

namespace Oro\Component\Layout;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LayoutRegistry implements LayoutRegistryInterface
{
    /** @var array */
    private $extensions = [];

    /** @var ExtensionInterface[] */
    private $sorted;

    /** @var BlockTypeInterface[] */
    private $types = [];

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
            throw new Exception\InvalidArgumentException(sprintf('Could not load block type "%s".', $name));
        }
        $this->types[$name] = $type;

        return $type;
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
            if ($extension->hasLayoutUpdates($id)) {
                $layoutUpdates = $extension->getLayoutUpdates($id);
                foreach ($layoutUpdates as $layoutUpdate) {
                    $layoutUpdate->updateLayout($layoutManipulator, $item);
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
