<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\Common\EventManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\TranslatableListener as GedmoTranslatableListener;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\TranslationBundle\EventListener\Datagrid\TranslatableListener;

class TranslatableListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatableListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new TranslatableListener();
    }

    public function testOnResultBefore()
    {
        $gedmoTranslatableListener = new GedmoTranslatableListener();
        $gedmoTranslatableListener->setTranslatableLocale('en');

        $eventManager = $this->createMock(EventManager::class);
        $eventManager->expects(self::once())
            ->method('getListeners')
            ->willReturn([[$gedmoTranslatableListener]]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::once())
            ->method('getEventManager')
            ->willReturn($eventManager);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('hasHint')
            ->with('oro_translation.translatable')
            ->willReturn(true);
        $query->expects(self::once())
            ->method('setHint')
            ->with('gedmo.translatable.locale', 'en');

        $ormResultBefore = new OrmResultBefore($datagrid, $query);
        $this->listener->onResultBefore($ormResultBefore);
    }

    public function testOnResultBeforeWithoutDatasource()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::once())
            ->method('getDatasource')
            ->willReturn(null);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::never())
            ->method('hasHint')
            ->with('oro_translation.translatable');
        $query->expects(self::never())
            ->method('setHint')
            ->with('gedmo.translatable.locale', 'en');

        $ormResultBefore = new OrmResultBefore($datagrid, $query);
        $this->listener->onResultBefore($ormResultBefore);
    }

    public function testOnResultBeforeWithoutTranslatableHint()
    {
        $entityManager = $this->createMock(EntityManager::class);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('hasHint')
            ->with('oro_translation.translatable')
            ->willReturn(false);
        $query->expects(self::never())
            ->method('setHint')
            ->with('gedmo.translatable.locale', 'en');

        $ormResultBefore = new OrmResultBefore($datagrid, $query);
        $this->listener->onResultBefore($ormResultBefore);
    }
}
