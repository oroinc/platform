<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Helper\TransitionWidgetHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TransitionWidgetHelperTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private TransitionWidgetHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->helper = new TransitionWidgetHelper($this->doctrineHelper);
    }

    /**
     * @dataProvider getOrCreateEntityReferenceDataProvider
     */
    public function testGetOrCreateEntityReference(string $entityClass, ?int $entityId = null): void
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
    public function testGetOrCreateEntityReferenceException(string $entityClass, ?int $entityId = null): void
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
