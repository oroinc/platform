<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\SaveParentEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SaveParentEntityTest extends ChangeRelationshipProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var SaveParentEntity */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new SaveParentEntity($this->doctrineHelper);
    }

    public function testProcessWhenNoParentEntity()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->processor->process($this->context);
    }

    public function testProcessForNotSupportedParentEntity()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setParentEntity([]);
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableParentEntity()
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn(null);

        $this->context->setParentEntity($entity);
        $this->processor->process($this->context);
    }

    public function testProcessForManageableParentEntity()
    {
        $entity = new \stdClass();

        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('flush')
            ->with(null);

        $this->context->setParentEntity($entity);
        $this->processor->process($this->context);
    }
}
