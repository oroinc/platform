<?php

namespace Oro\Component\Layout;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Extension\Core\CoreExtension;

class ExtensionManager implements ExtensionManagerInterface
{
    /** @var array */
    private $extensions = [];

    /** @var ExtensionInterface[] */
    private $sorted;

    /** @var BlockTypeInterface[] */
    private $types = [];

    public function __construct()
    {
        $this->addExtension(new CoreExtension(), -10);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockType($name)
    {
        if (!$name) {
            throw new Exception\InvalidArgumentException('The block type name must not be empty.');
        }
        if (!is_string($name)) {
            throw new Exception\UnexpectedTypeException($name, 'string');
        }

        if (!isset($this->types[$name])) {
            $type = $this->createBlockType($name);
            if (!$type) {
                throw new Exception\LogicException(
                    sprintf('The block type named "%s" was not found.', $name)
                );
            }
            if ($type->getName() !== $name) {
                throw new Exception\LogicException(
                    sprintf(
                        'The block type name does not match the name declared in the class implementing this type. '
                        . 'Expected "%s", given "%s".',
                        $name,
                        $type->getName()
                    )
                );
            }

            // add the created block type to the local cache
            $this->types[$name] = $type;
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions($name, OptionsResolverInterface $resolver)
    {
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasBlockTypeExtensions($name)) {
                $blockTypeExtensions = $extension->getBlockTypeExtensions($name);
                foreach ($blockTypeExtensions as $blockTypeExtension) {
                    $blockTypeExtension->setDefaultOptions($resolver);
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
            if ($extension->hasBlockTypeExtensions($name)) {
                $blockTypeExtensions = $extension->getBlockTypeExtensions($name);
                foreach ($blockTypeExtensions as $blockTypeExtension) {
                    $blockTypeExtension->buildBlock($builder, $options);
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
            if ($extension->hasBlockTypeExtensions($name)) {
                $blockTypeExtensions = $extension->getBlockTypeExtensions($name);
                foreach ($blockTypeExtensions as $blockTypeExtension) {
                    $blockTypeExtension->buildView($view, $block, $options);
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
            if ($extension->hasBlockTypeExtensions($name)) {
                $blockTypeExtensions = $extension->getBlockTypeExtensions($name);
                foreach ($blockTypeExtensions as $blockTypeExtension) {
                    $blockTypeExtension->finishView($view, $block, $options);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateLayout($id, LayoutManipulatorInterface $layoutManipulator)
    {
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasLayoutUpdates($id)) {
                $layoutUpdates = $extension->getLayoutUpdates($id);
                foreach ($layoutUpdates as $layoutUpdate) {
                    $layoutUpdate->updateLayout($layoutManipulator);
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
     * Creates a block type.
     *
     * @param string $name The name of the block type
     *
     * @return BlockTypeInterface|null
     */
    protected function createBlockType($name)
    {
        foreach ($this->getExtensions() as $extension) {
            if ($extension->hasBlockType($name)) {
                return $extension->getBlockType($name);
            }
        }

        return null;
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
