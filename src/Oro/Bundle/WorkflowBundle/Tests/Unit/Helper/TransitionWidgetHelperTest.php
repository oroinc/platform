<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Helper\TransitionWidgetHelper;

class TransitionWidgetHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var TransitionWidgetHelper */
    protected $helper;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->helper = new TransitionWidgetHelper($this->doctrineHelper);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset(
            $this->doctrineHelper,
            $this->formFactory,
            $this->workflowDataSerializer,
            $this->helper
        );
    }

    /**
     * @param string $entityClass
     * @param null|mixed $entityId
     *
     * @dataProvider getOrCreateEntityReferenceDataProvider
     */
    public function testGetOrCreateEntityReference($entityClass, $entityId = null)
    {
        if ($entityId) {
            $this->doctrineHelper->expects($this->once())->method('getEntityReference')->with($entityClass, $entityId);
        } else {
            $this->doctrineHelper->expects($this->once())->method('createEntityInstance')->with($entityClass);
        }

        $this->helper->getOrCreateEntityReference($entityClass, $entityId);
    }

    /**
     * @param string $entityClass
     * @param null|mixed $entityId
     *
     * @dataProvider getOrCreateEntityReferenceDataProvider
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testGetOrCreateEntityReferenceException($entityClass, $entityId = null)
    {
        if ($entityId) {
            $this->doctrineHelper->expects($this->once())
                ->method('getEntityReference')
                ->with($entityClass, $entityId)
                ->willThrowException(new NotManageableEntityException('message'));
        } else {
            $this->doctrineHelper->expects($this->once())
                ->method('createEntityInstance')
                ->with($entityClass)
                ->willThrowException(new NotManageableEntityException('message'));
        }

        $this->helper->getOrCreateEntityReference($entityClass, $entityId);
    }

    /**
     * @return \Generator
     */
    public function getOrCreateEntityReferenceDataProvider()
    {
        yield 'with id' => ['entityClass' => 'SomeClass', 'entityId' => 1];
        yield 'without id' => ['entityClass' => 'SomeClass'];
    }
}
