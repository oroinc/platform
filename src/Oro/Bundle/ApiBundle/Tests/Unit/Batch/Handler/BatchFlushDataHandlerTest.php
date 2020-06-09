<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandler;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class BatchFlushDataHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'Test\Entity';

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var BatchFlushDataHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->handler = new BatchFlushDataHandler(self::ENTITY_CLASS, $this->doctrineHelper);
    }

    public function testStartFlushData()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);

        $this->handler->startFlushData([$this->createMock(BatchUpdateItem::class)]);
    }

    public function testStartFlushDataWhenHandlerIsAlreadyStarted()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The flush data already started.');

        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);

        $this->handler->startFlushData([$this->createMock(BatchUpdateItem::class)]);
        $this->handler->startFlushData([$this->createMock(BatchUpdateItem::class)]);
    }

    public function testFinishFlushData()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::never())
            ->method('clear');

        $items = [$this->createMock(BatchUpdateItem::class)];
        $this->handler->startFlushData($items);
        $this->handler->finishFlushData($items);
    }

    public function testFinishFlushDataWhenHandlerIsNotStarted()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManagerForClass');

        $this->handler->finishFlushData([$this->createMock(BatchUpdateItem::class)]);
    }

    public function testClear()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('clear');

        $items = [$this->createMock(BatchUpdateItem::class)];
        $this->handler->startFlushData($items);
        $this->handler->finishFlushData($items);
        $this->handler->clear();
    }

    public function testClearWhenHandlerIsNotStarted()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManagerForClass');

        $this->handler->clear();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testFlushDataWhenNoError()
    {
        $item1 = $this->createMock(BatchUpdateItem::class);
        $item1Context = $this->createMock(BatchUpdateItemContext::class);
        $item1TargetContext = $this->createMock(CreateContext::class);
        $item1Entity = $this->createMock(\stdClass::class);
        $item1->expects(self::once())
            ->method('getContext')
            ->willReturn($item1Context);
        $item1Context->expects(self::once())
            ->method('getTargetAction')
            ->willReturn(ApiAction::CREATE);
        $item1Context->expects(self::once())
            ->method('hasErrors')
            ->willReturn(false);
        $item1Context->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($item1TargetContext);
        $item1TargetContext->expects(self::once())
            ->method('getResult')
            ->willReturn($item1Entity);

        $item2 = $this->createMock(BatchUpdateItem::class);
        $item2Context = $this->createMock(BatchUpdateItemContext::class);
        $item2->expects(self::once())
            ->method('getContext')
            ->willReturn($item2Context);
        $item2Context->expects(self::once())
            ->method('getTargetAction')
            ->willReturn(ApiAction::CREATE);
        $item2Context->expects(self::once())
            ->method('hasErrors')
            ->willReturn(true);

        $item3 = $this->createMock(BatchUpdateItem::class);
        $item3Context = $this->createMock(BatchUpdateItemContext::class);
        $item3->expects(self::once())
            ->method('getContext')
            ->willReturn($item3Context);
        $item3Context->expects(self::once())
            ->method('getTargetAction')
            ->willReturn(ApiAction::UPDATE);
        $item3Context->expects(self::never())
            ->method('hasErrors');

        $item4 = $this->createMock(BatchUpdateItem::class);
        $item4Context = $this->createMock(BatchUpdateItemContext::class);
        $item4->expects(self::once())
            ->method('getContext')
            ->willReturn($item4Context);
        $item4Context->expects(self::once())
            ->method('getTargetAction')
            ->willReturn(ApiAction::CREATE);
        $item4Context->expects(self::once())
            ->method('hasErrors')
            ->willReturn(false);
        $item4Context->expects(self::once())
            ->method('getTargetContext')
            ->willReturn(null);

        $item5 = $this->createMock(BatchUpdateItem::class);
        $item5Context = $this->createMock(BatchUpdateItemContext::class);
        $item5TargetContext = $this->createMock(CreateContext::class);
        $item5->expects(self::once())
            ->method('getContext')
            ->willReturn($item5Context);
        $item5Context->expects(self::once())
            ->method('getTargetAction')
            ->willReturn(ApiAction::CREATE);
        $item5Context->expects(self::once())
            ->method('hasErrors')
            ->willReturn(false);
        $item5Context->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($item5TargetContext);
        $item5TargetContext->expects(self::once())
            ->method('getResult')
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($item1Entity));
        $em->expects(self::exactly(2))
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('flush');
        $connection->expects(self::once())
            ->method('commit');
        $connection->expects(self::never())
            ->method('rollBack');
        $em->expects(self::never())
            ->method('clear');

        $items = [$item1, $item2, $item3, $item4, $item5];
        $this->handler->startFlushData($items);
        $this->handler->flushData($items);
    }

    public function testFlushDataWhenFlushFailed()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $exception = new \Exception('some error');

        $this->expectExceptionObject($exception);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::exactly(2))
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('flush')
            ->willThrowException($exception);
        $connection->expects(self::never())
            ->method('commit');
        $connection->expects(self::once())
            ->method('rollBack');
        $em->expects(self::never())
            ->method('clear');

        $items = [$this->createMock(BatchUpdateItem::class)];
        $this->handler->startFlushData($items);
        $this->handler->flushData($items);
    }

    public function testFlushDataWhenHandlerNotStarted()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The flush data is not started.');

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManagerForClass');

        $this->handler->flushData([$this->createMock(BatchUpdateItem::class)]);
    }
}
