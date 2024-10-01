<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Extension;

use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;
use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

/**
 * Collect block views, options and vars.
 */
class DataCollectorExtension extends AbstractBlockTypeExtension
{
    /** @var LayoutDataCollector */
    private $dataCollector;

    public function __construct(LayoutDataCollector $dataCollector)
    {
        $this->dataCollector = $dataCollector;
    }

    #[\Override]
    public function finishView(BlockView $view, BlockInterface $block)
    {
        $this->dataCollector->collectBlockView($block, $view);
    }

    #[\Override]
    public function getExtendedType()
    {
        return BaseType::NAME;
    }
}
