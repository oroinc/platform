<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Extension;

use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;

use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

/**
 * Collect block views, options and vars.
 */
class DataCollectorExtension extends AbstractBlockTypeExtension
{
    /** @var LayoutDataCollector */
    private $dataCollector;

    /**
     * @param LayoutDataCollector $dataCollector
     */
    public function __construct(LayoutDataCollector $dataCollector)
    {
        $this->dataCollector = $dataCollector;
    }

    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, array $options)
    {
        $this->dataCollector->collectBuildBlockOptions($builder->getId(), $builder->getTypeName(), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $this->dataCollector->collectBuildViewOptions($block, get_class($block), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $this->dataCollector->collectFinishViewOptions($block, $options);
        $this->dataCollector->collectBlockTree($block, $view);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return BaseType::NAME;
    }
}
