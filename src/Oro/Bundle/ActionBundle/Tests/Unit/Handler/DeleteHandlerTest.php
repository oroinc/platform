<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Handler;

use Oro\Bundle\ActionBundle\Handler\DeleteHandler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler as BaseDeleteHandler;

class DeleteHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var BaseDeleteHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $deleteHandler;

    /** @var ApiEntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $apiEntityManager;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var DeleteHandler */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->deleteHandler = $this->getMockBuilder('Oro\Bundle\SoapBundle\Handler\DeleteHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->apiEntityManager = $this->getMockBuilder('Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new DeleteHandler($this->deleteHandler, $this->apiEntityManager, $this->doctrineHelper);
    }

    public function testHandleDelete()
    {
        $entity = 'TestEntity';
        $id = '123';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entity);

        $this->apiEntityManager->expects($this->once())
            ->method('setClass')
            ->with($entity);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityIdentifier')
            ->with($entity)
            ->willReturn($id);

        $this->deleteHandler->expects($this->once())
            ->method('handleDelete')
            ->with($id, $this->apiEntityManager);

        $this->handler->handleDelete($entity);
    }
}
