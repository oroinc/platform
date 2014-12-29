<?php

namespace Oro\Bundle\SegmentBundle\Grid;

use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\ReportBundle\Grid\BaseReportConfigurationBuilder;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Model\DatagridSourceSegmentProxy;

class SegmentDatagridConfigurationBuilder extends BaseReportConfigurationBuilder
{
    /**
     * @param AbstractQueryDesigner $source
     */
    public function setSource(AbstractQueryDesigner $source)
    {
        $em = $this->doctrine->getManagerForClass($source->getEntity());

        $this->source = new DatagridSourceSegmentProxy($source, $em);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $config = parent::getConfiguration();

        $config->offsetSetByPath('[source][acl_resource]', 'oro_segment_view');
        $config->offsetSetByPath(ExportExtension::EXPORT_OPTION_PATH, true);

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($gridName)
    {
        return (strpos($gridName, Segment::GRID_PREFIX) === 0);
    }
}
