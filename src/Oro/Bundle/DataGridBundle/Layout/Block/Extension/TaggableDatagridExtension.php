<?php

namespace Oro\Bundle\DataGridBundle\Layout\Block\Extension;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

/**
 * This extension extends all links with "enable_tagging" option, that
 * can be used to enable watching of changes in the grid.
 */
class TaggableDatagridExtension extends AbstractBlockTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['enable_tagging' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['enable_tagging'] = $options['enable_tagging'];
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        BlockUtils::registerPlugin($view, 'taggable_datagrid');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'datagrid';
    }
}
