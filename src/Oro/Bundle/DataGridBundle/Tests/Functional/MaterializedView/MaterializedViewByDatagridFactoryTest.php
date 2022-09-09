<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\MaterializedView;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Manager as DatagridManager;
use Oro\Bundle\DataGridBundle\MaterializedView\MaterializedViewByDatagridFactory;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewManager;
use Oro\Bundle\PlatformBundle\Tests\Functional\MaterializedView\MaterializedViewsAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DoctrineUtils\ORM\Walker\MaterializedViewOutputResultModifier;

class MaterializedViewByDatagridFactoryTest extends WebTestCase
{
    use MaterializedViewsAwareTestTrait;

    private MaterializedViewByDatagridFactory $materializedViewByDatagridFactory;

    private MaterializedViewManager $materializedViewManager;

    private DatagridManager $datagridManager;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->materializedViewByDatagridFactory = self::getContainer()
            ->get('oro_datagrid.materialized_view.factory.by_datagrid');
        $this->materializedViewManager = self::getContainer()->get('oro_platform.materialized_view.manager');
        $this->datagridManager = self::getContainer()->get('oro_datagrid.datagrid.manager');
    }

    public static function tearDownAfterClass(): void
    {
        self::deleteAllMaterializedViews(self::getContainer());

        parent::tearDownAfterClass();
    }

    public function testCreateByDatagrid(): void
    {
        $datagrid = $this->datagridManager->getDatagrid('users-grid');
        $materializedViewEntity = $this->materializedViewByDatagridFactory->createByDatagrid($datagrid);

        self::assertTrue($materializedViewEntity->isWithData());

        $materializedViewName = $materializedViewEntity->getName();
        self::assertSame(
            $materializedViewEntity,
            $this->materializedViewManager->findByName($materializedViewName)
        );

        $materializedViewInfo = self::getMaterializedViewInfo(self::getContainer(), $materializedViewName);
        self::assertNotNull($materializedViewInfo);
        self::assertTrue($materializedViewInfo['ispopulated']);

        $datasourceWithHint = clone $datagrid->getDatasource();
        $datasourceConfig = $datagrid->getConfig()->offsetGetByPath(DatagridConfiguration::DATASOURCE_PATH, []);
        $datasourceConfig['hints'][] = [
            'name' => MaterializedViewOutputResultModifier::USE_MATERIALIZED_VIEW,
            'value' => $materializedViewName,
        ];
        $datasourceWithHint->process($datagrid, $datasourceConfig);

        self::assertEquals($datagrid->getDatasource()->getResults(), $datasourceWithHint->getResults());
    }
}
