<?php

namespace Oro\Component\Layout;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

interface BlockTypeExtensionInterface
{
    /**
     * Builds the block.
     *
     * This method is called after the extended type has built the block
     * and can be used to further modify the block.
     *
     * @see BlockTypeInterface::buildForm()
     *
     * @param BlockBuilderInterface $builder
     * @param array                 $options
     */
    public function buildBlock(BlockBuilderInterface $builder, array $options);

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
     * @param array          $options
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options);

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
     * @param array          $options
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options);

    /**
     * Overrides the default options from the extended type.
     *
     * @param OptionsResolverInterface $resolver The resolver for the options.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver);

    /**
     * Returns the name of the block type being extended.
     *
     * @return string The name of the block type being extended
     */
    public function getExtendedType();
}
