<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Helper\TransitionWidgetHelper;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TransitionWidgetHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var TransitionWidgetHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->helper = new TransitionWidgetHelper($this->doctrineHelper);
    }

    /**
     * @dataProvider getOrCreateEntityReferenceDataProvider
     */
    public function testGetOrCreateEntityReference(string $entityClass, int $entityId = null)
    {
        if ($entityId) {
            $this->doctrineHelper->expects($this->once())
                ->method('getEntityReference')
                ->with($entityClass, $entityId);
        } else {
            $this->doctrineHelper->expects($this->once())
                ->method('createEntityInstance')
                ->with($entityClass);
        }

        $this->helper->getOrCreateEntityReference($entityClass, $entityId);
    }

    /**
     * @dataProvider getOrCreateEntityReferenceDataProvider
     */
    public function testGetOrCreateEntityReferenceException(string $entityClass, int $entityId = null)
    {
        $this->expectException(BadRequestHttpException::class);
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

    public function getOrCreateEntityReferenceDataProvider(): array
    {
        return [
            'with id' => ['entityClass' => 'SomeClass', 'entityId' => 1],
            'without id' => ['entityClass' => 'SomeClass']
        ];
    }
}
