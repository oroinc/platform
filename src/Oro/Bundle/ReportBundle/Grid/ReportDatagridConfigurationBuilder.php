<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Export\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Oro\Bundle\EntityPaginationBundle\Datagrid\EntityPaginationExtension;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;

/**
 * Enables entity pagination and grid export when applicable for report grids.
 * Results of the builder is cached by ReportDatagridConfigurationProvider.
 */
class ReportDatagridConfigurationBuilder extends BaseReportConfigurationBuilder
{
    /**
     * @var DatagridDateGroupingBuilder
     */
    protected $dateGroupingBuilder;

    protected int $exportPageSize = 200;

    public function setExportPageSize(int $exportPageSize): void
    {
        $this->exportPageSize = $exportPageSize;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $config = parent::getConfiguration();

        $config->offsetSetByPath('[source][acl_resource]', 'oro_report_view');
        $this->enableExportIfApplicable($config);
        $config->offsetSetByPath(EntityPaginationExtension::ENTITY_PAGINATION_PATH, true);
        if (!in_array('HINT_TRANSLATABLE', $config->offsetGetByPath('[source][hints]', []))) {
            $config->offsetAddToArrayByPath('[source][hints]', ['HINT_TRANSLATABLE']);
        }

        $this->dateGroupingBuilder->applyDateGroupingFilterIfRequired($config, $this->source);

        return $config;
    }

    /**
     * @param DatagridDateGroupingBuilder $dateGroupingBuilder
     * @return $this
     */
    public function setDateGroupingBuilder(DatagridDateGroupingBuilder $dateGroupingBuilder)
    {
        $this->dateGroupingBuilder = $dateGroupingBuilder;

        return $this;
    }

    private function enableExportIfApplicable(DatagridConfiguration $config): void
    {
        if ($this->hasSplittableByIdGroupBy()) {
            $commonOptions = [
                'page_size' => $this->exportPageSize,
                'export_by_pages' => true,
            ];
            $exportOption = [
                'csv' => ['label' => 'oro.grid.export.csv'] + $commonOptions,
                'xlsx' => [
                        'label' => 'oro.grid.export.xlsx',
                        'show_max_export_records_dialog' => true,
                        'max_export_records' => Configuration::XLSX_MAX_EXPORT_RECORDS,
                    ] + $commonOptions,
            ];
        } else {
            $exportOption = true;
        }

        $config->offsetSetByPath(ExportExtension::EXPORT_OPTION_PATH, $exportOption);
    }

    /**
     * Check if a given source may return duplicated rows for a root entity when filtered by root entity ids.
     *
     * Example: Some line items are used as root entity. Grouping done by columns of entity that owns
     * line items collection. In this case during export line item ids will be used for batching, but line items
     * of same owning entity may be processed within different batches. Such situation will lead to duplicate records
     * of owning entity in resulting file. Also, such export will be no same to original grid.
     */
    private function hasSplittableByIdGroupBy(): bool
    {
        $reportDefinition = QueryDefinitionUtil::decodeDefinition($this->source->getDefinition());
        $groupBy = $reportDefinition['grouping_columns'] ?? [];
        $joinIdentifierHelper = new JoinIdentifierHelper($this->source->getEntity());
        $entityMetadata = $this->doctrineHelper->getEntityMetadataForClass($this->source->getEntity(), false);
        if (!$entityMetadata) {
            return false;
        }
        $associations = $entityMetadata->getAssociationNames();
        $id = $entityMetadata->getSingleIdentifierFieldName();

        $isSplittable = false;
        foreach ($groupBy as $groupByDefinition) {
            $parts = $joinIdentifierHelper->explodeJoinIdentifier($groupByDefinition['name']);
            $fieldName = $parts[0];

            // When grouped by root entity identifier - filter by ID will give same results
            if ($fieldName === $id) {
                return false;
            }

            // When MANY-TO-ONE associations are used in group by, then results filtered by IDs of root entity
            // will give duplicates for -TO-ONE relation when processed in batched by root entity IDs during export
            if (\in_array($fieldName, $associations, true)) {
                $mapping = $entityMetadata->getAssociationMapping($fieldName);
                if ($mapping['type'] === ClassMetadata::MANY_TO_ONE) {
                    // Do not break here, as we may find ID field later within this loop.
                    $isSplittable = true;
                }
            }
        }

        return $isSplittable;
    }
}
