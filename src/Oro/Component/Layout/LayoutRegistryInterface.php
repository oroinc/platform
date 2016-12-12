<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;

/**
 * The central registry of the Layout component.
 */
interface LayoutRegistryInterface
{
    /**
     * Returns a block type by name.
     *
     * @param string $name The block type name
     *
     * @return BlockTypeInterface
     *
     * @throws Exception\UnexpectedTypeException  if the passed name is not a string
     * @throws Exception\InvalidArgumentException if the block type cannot be loaded
     */
    public function getType($name);

    /**
     * Returns all registered extensions for the given block type.
     *
     * @param string $name The block type name
     *
     * @return BlockTypeExtensionInterface[]
     */
    public function getTypeExtensions($name);

    /**
     * Returns all registered layout context configurators.
     *
     * @return ContextConfiguratorInterface[]
     */
    public function getContextConfigurators();

    /**
     * Returns a data provider by name or NULL if a data provider does not exist.
     *
     * @param string $name The name of the data provider
     *
     * @return object|null
     *
     * @throws Exception\UnexpectedTypeException if the passed name is not a string
     */
    public function findDataProvider($name);

    /**
     * Sets the default options for a block type.
     *
     * @param string $name The block type name
     * @param OptionsResolver $resolver The resolver for the options.
     */
    public function configureOptions($name, OptionsResolver $resolver);

    /**
     * Builds the block.
     *
     * This method is called after the extended type has built the block
     * and can be used to further modify the block and prepare block options.
     *
     * @see BlockTypeInterface::buildForm()
     *
     * @param string                $name    The block type name
     * @param BlockBuilderInterface $builder The block builder
     * @param Options               $options The options
     */
    public function buildBlock($name, BlockBuilderInterface $builder, Options $options);

    /**
     * Builds the block view.
     *
     * This method is called after the extended type has built the view
     * and can be used to further modify the view.
     *
     * @see FormTypeInterface::buildView()
     *
     * @param string         $name    The block type name
     * @param BlockView      $view    The block view object
     * @param BlockInterface $block   The block configuration
     * @param Options        $options The options
     */
    public function buildView($name, BlockView $view, BlockInterface $block, Options $options);

    /**
     * Finishes the block view.
     *
     * This method is called after the extended type has finished the view
     * and can be used to further modify the view.
     *
     * @see FormTypeInterface::finishView()
     *
     * @param string         $name    The block type name
     * @param BlockView      $view    The block view object
     * @param BlockInterface $block   The block configuration
     */
    public function finishView($name, BlockView $view, BlockInterface $block);

    /**
     * Executes layout updates for the given layout item.
     *
     * @param string                     $id                The id or alias of the layout item
     * @param LayoutManipulatorInterface $layoutManipulator The layout manipulator
     * @param LayoutItemInterface        $item              The layout item for which the update is executed
     *
     * @return mixed
     */
    public function updateLayout($id, LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item);

    /**
     * Configures the layout context.
     *
     * @param ContextInterface $context The context
     */
    public function configureContext(ContextInterface $context);
}
