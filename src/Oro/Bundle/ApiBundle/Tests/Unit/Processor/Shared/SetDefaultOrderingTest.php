<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultOrdering;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group as Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SetDefaultOrderingTest extends GetListProcessorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var SetDefaultOrdering */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new SetDefaultOrdering($this->doctrineHelper);
        $this->context->setClassName(Entity::class);
        $this->context->setConfig(new EntityDefinitionConfig());
    }

    public function testProcessWhenSetDefaultOrderingIsAlreadyProcessed()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityIdentifierFieldNamesForClass');

        $this->context->setProcessed(SetDefaultOrdering::OPERATION_NAME);
        $this->context->setCriteria(new Criteria());
        $this->processor->process($this->context);

        self::assertEquals([], $this->context->getCriteria()->getOrderings());
        self::assertTrue($this->context->isProcessed(SetDefaultOrdering::OPERATION_NAME));
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityIdentifierFieldNamesForClass');

        $this->processor->process($this->context);
        self::assertNull($this->context->getCriteria());
        self::assertTrue($this->context->isProcessed(SetDefaultOrdering::OPERATION_NAME));
    }

    public function testProcessWhenCriteriaObjectAlreadyHasOrdering()
    {
        $ordering = ['field1' => 'DESC'];

        $criteria = new Criteria();
        $criteria->orderBy($ordering);

        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityIdentifierFieldNamesForClass');

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        self::assertEquals($ordering, $this->context->getCriteria()->getOrderings());
        self::assertTrue($this->context->isProcessed(SetDefaultOrdering::OPERATION_NAME));
    }

    public function testProcessForManageableEntity()
    {
        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->willReturn(Entity::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with(Entity::class)
            ->willReturn(['id']);

        $this->context->setCriteria(new Criteria());
        $this->processor->process($this->context);

        self::assertEquals(['id' => 'ASC'], $this->context->getCriteria()->getOrderings());
        self::assertTrue($this->context->isProcessed(SetDefaultOrdering::OPERATION_NAME));
    }

    public function testProcessForNotManageableEntity()
    {
        $config = $this->context->getConfig();
        $config->setIdentifierFieldNames(['renamedId']);
        $config->addField('renamedId')->setPropertyPath('originalId');

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->willReturn(null);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityIdentifierFieldNamesForClass');

        $this->context->setCriteria(new Criteria());
        $this->processor->process($this->context);

        self::assertEquals(['originalId' => 'ASC'], $this->context->getCriteria()->getOrderings());
        self::assertTrue($this->context->isProcessed(SetDefaultOrdering::OPERATION_NAME));
    }
}
