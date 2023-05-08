<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Processor\Update\SaveEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SaveEntityTest extends FormProcessorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var FlushDataHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $flushDataHandler;

    /** @var SaveEntity */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context->setClassName(\stdClass::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->flushDataHandler = $this->createMock(FlushDataHandlerInterface::class);

        $this->processor = new SaveEntity($this->doctrineHelper, $this->flushDataHandler);
    }

    public function testProcessWhenEntityAlreadySaved()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManagerForClass');

        $this->flushDataHandler->expects(self::never())
            ->method('flushData');

        $this->context->setProcessed(SaveEntity::OPERATION_NAME);
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoEntity()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManagerForClass');

        $this->flushDataHandler->expects(self::never())
            ->method('flushData');

        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForNotSupportedEntity()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManagerForClass');

        $this->flushDataHandler->expects(self::never())
            ->method('flushData');

        $this->context->setResult([]);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForNotManageableEntity()
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with(\stdClass::class, $this->context->getConfig())
            ->willReturn(null);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManagerForClass');

        $this->flushDataHandler->expects(self::never())
            ->method('flushData');

        $this->context->setResult($entity);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForManageableEntity()
    {
        $entity = new \stdClass();

        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with(\stdClass::class, $this->context->getConfig())
            ->willReturn(\stdClass::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($em);

        $this->flushDataHandler->expects(self::once())
            ->method('flushData')
            ->with(self::identicalTo($em), self::isInstanceOf(FlushDataHandlerContext::class))
            ->willReturnCallback(function (EntityManagerInterface $em, FlushDataHandlerContext $context) {
                self::assertSame($this->context, $context->getEntityContexts()[0]);
                self::assertSame($this->context->getSharedData(), $context->getSharedData());
            });

        $this->context->setResult($entity);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }
}
