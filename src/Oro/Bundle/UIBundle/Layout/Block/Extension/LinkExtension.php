<?php

namespace Oro\Bundle\UIBundle\Layout\Block\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\LinkType;

/**
 * This extension extends all links with "with_page_parameters" option, that
 * can be used to add current page query string parameters to the link url.
 */
class LinkExtension extends AbstractBlockTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['with_page_parameters']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['with_page_parameters'] = isset($options['with_page_parameters'])
            ? $options['with_page_parameters']
            : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return LinkType::NAME;
    }
}
