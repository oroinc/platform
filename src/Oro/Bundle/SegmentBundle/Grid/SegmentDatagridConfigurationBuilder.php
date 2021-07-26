<?php

namespace Oro\Bundle\SegmentBundle\Grid;

use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\ReportBundle\Grid\BaseReportConfigurationBuilder;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Model\SegmentDatagridConfigurationQueryDesigner;

/**
 * Builds a datagrid configuration based on a query definition created by the query designer for a segment.
 */
class SegmentDatagridConfigurationBuilder extends BaseReportConfigurationBuilder
{
    /**
     * {@inheritdoc}
     */
    public function setSource(AbstractQueryDesigner $source)
    {
        $this->source = new SegmentDatagridConfigurationQueryDesigner(
            $source,
            $this->doctrineHelper->getEntityManagerForClass($source->getEntity())
        );
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
        return str_starts_with($gridName, Segment::GRID_PREFIX);
    }
}
