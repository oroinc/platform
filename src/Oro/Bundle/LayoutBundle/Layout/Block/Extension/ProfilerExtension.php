<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Extension;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

use Oro\Bundle\LayoutBundle\Request\LayoutHelper;

/**
 * Adds 'data-layout-block-id' attribute for all block types and store block id in this attribute.
 * This attribute is optional and visible when isProfilerEnabled set TRUE.
 */
class ProfilerExtension extends AbstractBlockTypeExtension
{
    /**
     * @var LayoutHelper
     */
    protected $layoutHelper;

    /**
     * @param LayoutHelper $layoutHelper
     */
    public function __construct(LayoutHelper $layoutHelper)
    {
        $this->layoutHelper = $layoutHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        if ($this->layoutHelper->isProfilerEnabled()) {
            $view->vars['attr']['data-layout-block-id'] = $view->vars['id'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return BaseType::NAME;
    }
}
