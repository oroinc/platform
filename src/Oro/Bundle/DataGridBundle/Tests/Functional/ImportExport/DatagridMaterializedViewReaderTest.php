<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\ImportExport;

use Oro\Bundle\DataGridBundle\Datagrid\Manager as DatagridManager;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridMaterializedViewReader;
use Oro\Bundle\DataGridBundle\MaterializedView\MaterializedViewByDatagridFactory;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\PlatformBundle\Tests\Functional\MaterializedView\MaterializedViewsAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DatagridMaterializedViewReaderTest extends WebTestCase
{
    use MaterializedViewsAwareTestTrait;

    private MaterializedViewByDatagridFactory $materializedViewByDatagridFactory;

    private DatagridMaterializedViewReader $datagridMaterializedViewReader;

    private DatagridManager $datagridManager;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->materializedViewByDatagridFactory = self::getContainer()
            ->get('oro_datagrid.materialized_view.factory.by_datagrid');
        $this->datagridMaterializedViewReader = self::getContainer()
            ->get('oro_datagrid.importexport.materialized_view_reader');
        $this->datagridManager = self::getContainer()->get('oro_datagrid.datagrid.manager');
    }

    public static function tearDownAfterClass(): void
    {
        self::deleteAllMaterializedViews(self::getContainer());

        parent::tearDownAfterClass();
    }

    /**
     * @dataProvider gridParametersDataProvider
     */
    public function testRead(array $gridParameters): void
    {
        $gridName = 'users-grid';
        $datagrid = $this->datagridManager->getDatagrid($gridName, $gridParameters);
        $materializedViewEntity = $this->materializedViewByDatagridFactory->createByDatagrid($datagrid);

        $context = new Context([
            'materializedViewName' => $materializedViewEntity->getName(),
            'gridName' => $gridName,
            'rowsOffset' => 0,
            'rowsLimit' => 10,
        ]);
        $this->datagridMaterializedViewReader->setImportExportContext($context);

        $rows = [];
        do {
            $row = $this->datagridMaterializedViewReader->read();
            if ($row !== null) {
                // Actions config might contain CSRF token with random value that will fail the equals assertion.
                unset($row['action_configuration']);
                $rows[] = $row;
            }
        } while ($row !== null);

        self::assertNotEmpty($rows);

        $expectedData = $datagrid->getData()->getData();
        array_walk($expectedData, static function (array &$row) {
            // Actions config might contain CSRF token with random value that will fail the equals assertion.
            unset($row['action_configuration']);
        });
        self::assertEquals($rows, $expectedData);
    }

    public function gridParametersDataProvider(): array
    {
        return [
            'empty parameters' => ['gridParameters' => []],
            'with string filter' => [
                'gridParameters' => [
                    AbstractFilterExtension::FILTER_ROOT_PARAM => [
                        'username' => [
                            'value' => 'admin',
                            'type' => TextFilterType::TYPE_CONTAINS,
                        ],
                    ],
                ],
            ],
        ];
    }
}
