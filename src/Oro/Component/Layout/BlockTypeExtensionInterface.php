<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;

interface BlockTypeExtensionInterface
{
    /**
     * Builds the block.
     *
     * This method is called after the extended type has built the block
     * and can be used to further modify the block and prepare block options.
     *
     * @see BlockTypeInterface::buildForm()
     *
     * @param BlockBuilderInterface $builder
     * @param Options               $options
     */
    public function buildBlock(BlockBuilderInterface $builder, Options $options);

    /**
     * Builds the block view.
     *
     * This method is called after the extended type has built the view
     * and can be used to further modify the view.
     *
     * @see FormTypeInterface::buildView()
     *
     * @param BlockView      $view
     * @param BlockInterface $block
     * @param Options        $options
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options);

    /**
     * Finishes the block view.
     *
     * This method is called after the extended type has finished the view
     * and can be used to further modify the view.
     *
     * @see FormTypeInterface::finishView()
     *
     * @param BlockView      $view
     * @param BlockInterface $block
     */
    public function finishView(BlockView $view, BlockInterface $block);

    /**
     * Overrides the default options from the extended type.
     *
     * @param OptionsResolver $resolver The resolver for the options.
     */
    public function configureOptions(OptionsResolver $resolver);

    /**
     * Returns the name of the block type being extended.
     *
     * @return string The name of the block type being extended
     */
    public function getExtendedType();
}
