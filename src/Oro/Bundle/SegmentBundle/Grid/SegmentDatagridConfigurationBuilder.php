<?php

namespace Oro\Bundle\SegmentBundle\Grid;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Model\DatagridSourceSegmentProxy;
use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;

class SegmentDatagridConfigurationBuilder extends DatagridConfigurationBuilder
{
    public function __construct(
        $gridName,
        Segment $segment,
        FunctionProviderInterface $functionProvider,
        ManagerRegistry $doctrine
    ) {
        parent::__construct(
            $gridName,
            new DatagridSourceSegmentProxy($segment, $doctrine->getManager()),
            $functionProvider,
            $doctrine
        );

        $this->config->offsetSetByPath('[source][acl_resource]', 'oro_segment_view');
        $this->config->offsetSetByPath(ExportExtension::EXPORT_OPTION_PATH, true);
    }
}
