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
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
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

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->processor->process($this->context);
        self::assertNull($this->context->getCriteria());
    }

    public function testProcessWhenCriteriaObjectAlreadyHasOrdering()
    {
        $ordering = ['field1' => 'DESC'];

        $criteria = new Criteria();
        $criteria->orderBy($ordering);

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        self::assertEquals($ordering, $this->context->getCriteria()->getOrderings());
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
    }
}
