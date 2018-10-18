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
    /** @var TranslatableListener $listener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->listener = new TranslatableListener();
    }

    public function testOnResultBefore()
    {
        $gedmoTranslatableListener = new GedmoTranslatableListener();
        $gedmoTranslatableListener->setTranslatableLocale('en');

        /** @var EventManager|\PHPUnit\Framework\MockObject\MockObject $eventManager */
        $eventManager = self::createMock(EventManager::class);
        $eventManager->expects(self::once())->method('getListeners')->willReturn([[$gedmoTranslatableListener]]);

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = self::createMock(EntityManager::class);
        $entityManager->expects(self::once())->method('getEventManager')->willReturn($eventManager);

        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = self::createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())->method('getEntityManager')->willReturn($entityManager);

        /** @var OrmDatasource|\PHPUnit\Framework\MockObject\MockObject $dataSource */
        $dataSource = self::createMock(OrmDatasource::class);
        $dataSource->expects(self::once())->method('getQueryBuilder')->willReturn($queryBuilder);

        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = self::createMock(DatagridInterface::class);
        $datagrid->expects(self::once())->method('getDatasource')->willReturn($dataSource);

        /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject $query */
        $query = self::createMock(AbstractQuery::class);
        $query->expects(self::once())->method('hasHint')->with('oro_translation.translatable')->willReturn(true);
        $query->expects(self::once())->method('setHint')->with('gedmo.translatable.locale', 'en');

        /** @var OrmResultBefore $ormResultBefore */
        $ormResultBefore = new OrmResultBefore($datagrid, $query);
        $this->listener->onResultBefore($ormResultBefore);
    }

    public function testOnResultBeforeWithoutDatasource()
    {
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = self::createMock(DatagridInterface::class);
        $datagrid->expects(self::once())->method('getDatasource')->willReturn(null);

        /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject $query */
        $query = self::createMock(AbstractQuery::class);
        $query->expects(self::never())->method('hasHint')->with('oro_translation.translatable');
        $query->expects(self::never())->method('setHint')->with('gedmo.translatable.locale', 'en');

        $ormResultBefore = new OrmResultBefore($datagrid, $query);
        $this->listener->onResultBefore($ormResultBefore);
    }

    public function testOnResultBeforeWithoutTranslatableHint()
    {
        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = self::createMock(EntityManager::class);

        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = self::createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())->method('getEntityManager')->willReturn($entityManager);

        /** @var OrmDatasource|\PHPUnit\Framework\MockObject\MockObject $dataSource */
        $dataSource = self::createMock(OrmDatasource::class);
        $dataSource->expects(self::once())->method('getQueryBuilder')->willReturn($queryBuilder);

        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = self::createMock(DatagridInterface::class);
        $datagrid->expects(self::once())->method('getDatasource')->willReturn($dataSource);

        /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject $query */
        $query = self::createMock(AbstractQuery::class);
        $query->expects(self::once())->method('hasHint')->with('oro_translation.translatable')->willReturn(false);
        $query->expects(self::never())->method('setHint')->with('gedmo.translatable.locale', 'en');

        /** @var OrmResultBefore $ormResultBefore */
        $ormResultBefore = new OrmResultBefore($datagrid, $query);
        $this->listener->onResultBefore($ormResultBefore);
    }
}
