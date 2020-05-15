<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Datagrid\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\LocaleBundle\Datagrid\EventListener\EnabledLocalizationsGridListener;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;

class EnabledLocalizationsGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var EnabledLocalizationsGridListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->listener = new EnabledLocalizationsGridListener($this->configManager);
    }

    public function testOnBuildAfterWhenNoOrmDatasource()
    {
        $datasource = $this->createMock(DatasourceInterface::class);
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $this->configManager->expects($this->never())
            ->method('get');

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfter()
    {
        $enabledLocalizationIds = [1, 2];
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('ids', $enabledLocalizationIds);
        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
                false,
                false,
                null
            )
            ->willReturn($enabledLocalizationIds);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }
}
