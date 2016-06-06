<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

interface BlockTypeInterface
{
    /**
     * Builds the block.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the block.
     *
     * @param BlockBuilderInterface $builder
     * @param array                 $options
     */
    public function buildBlock(BlockBuilderInterface $builder, array $options);

    /**
     * Builds the block view.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the view.
     *
     * A view of a block is built before the views of the child blocks are built.
     * This means that you cannot access child views in this method. If you need
     * to do so, move your logic to {@link finishView()} instead.
     *
     * @param BlockView      $view
     * @param BlockInterface $block
     * @param array          $options
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options);

    /**
     * Finishes the block view.
     *
     * This method gets called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the view.
     *
     * When this method is called, views of the block's children have already
     * been built and finished and can be accessed. You should only implement
     * such logic in this method that actually accesses child views. For everything
     * else you are recommended to implement {@link buildView()} instead.
     *
     * @param BlockView      $view
     * @param BlockInterface $block
     * @param array          $options
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options);

    /**
     * Sets the default options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options.
     */
    public function configureOptions(OptionsResolver $resolver);

    /**
     * Returns the name of the parent type.
     *
     * @return string|null The name of the parent type if any; otherwise, null.
     */
    public function getParent();

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName();
}
