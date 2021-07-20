<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Grid;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadOrganizationsWithUsersData;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentWithToManyFiltersData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SegmentWithConditionsGroupTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * @var bool
     */
    private $groupingEnabled;

    /**
     * @var ConfigManager
     */
    private $configManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrganizationsWithUsersData::class,
            LoadSegmentWithToManyFiltersData::class
        ]);
        $this->configManager = self::getConfigManager(null);
        $this->groupingEnabled = $this->configManager
            ->get('oro_query_designer.conditions_group_merge_same_entity_conditions');
    }

    protected function tearDown(): void
    {
        self::getConfigManager('global')
            ->set('oro_query_designer.conditions_group_merge_same_entity_conditions', $this->groupingEnabled);
    }

    public function testSegmentWithGroupedFilters()
    {
        $this->assertSegmentResults(
            $this->getReference(LoadSegmentWithToManyFiltersData::SEGMENT_FILTER_GROUP),
            [
                'segment.organization.1'
            ]
        );
    }

    public function testSegmentWithoutGroupedFilters()
    {
        $this->assertSegmentResults(
            $this->getReference(LoadSegmentWithToManyFiltersData::SEGMENT_FILTER_NO_GROUP),
            [
                'segment.organization.1',
                'segment.organization.2'
            ]
        );
    }

    public function testSegmentWithGroupedFiltersGroupingDisabled()
    {
        self::getConfigManager('global')
            ->set('oro_query_designer.conditions_group_merge_same_entity_conditions', false);

        $this->assertSegmentResults(
            $this->getReference(LoadSegmentWithToManyFiltersData::SEGMENT_FILTER_GROUP),
            [
                'segment.organization.1',
                'segment.organization.2'
            ]
        );
    }

    protected function assertSegmentResults(Segment $segment, array $expected)
    {
        $gridManager = $this->getContainer()->get('oro_datagrid.datagrid.manager');

        $grid = $gridManager->getDatagrid('oro_segment_grid_' . $segment->getId());
        $ds = $grid->getAcceptedDatasource();
        $actual = array_map(
            function (ResultRecordInterface $record) {
                return $record->getValue('c1');
            },
            $ds->getResults()
        );

        $this->assertEquals($expected, $actual);
    }
}
