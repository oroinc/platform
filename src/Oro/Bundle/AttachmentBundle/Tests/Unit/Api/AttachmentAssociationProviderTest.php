<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Api\AttachmentAssociationProvider;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AttachmentAssociationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AttachmentAssociationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentAssociationHelper;

    /** @var AttachmentAssociationProvider */
    private $attachmentAssociationProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->attachmentAssociationHelper = $this->createMock(AttachmentAssociationHelper::class);

        $this->attachmentAssociationProvider = new AttachmentAssociationProvider(
            $this->doctrineHelper,
            $this->attachmentAssociationHelper
        );
    }

    public function testGetAttachmentAssociationNameForEntityWithEnabledAttachments(): void
    {
        $entityClass = 'Test\Entity';
        $version = 'latest';
        $requestType = new RequestType([RequestType::REST]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->attachmentAssociationHelper->expects(self::once())
            ->method('isAttachmentAssociationEnabled')
            ->with($entityClass)
            ->willReturn(true);

        $expected = ExtendHelper::buildAssociationName($entityClass);

        self::assertSame(
            $expected,
            $this->attachmentAssociationProvider->getAttachmentAssociationName($entityClass, $version, $requestType)
        );
        // test memory cache
        self::assertSame(
            $expected,
            $this->attachmentAssociationProvider->getAttachmentAssociationName($entityClass, $version, $requestType)
        );
    }

    public function testGetAttachmentAssociationNameForEntityWithDisabledAttachments(): void
    {
        $entityClass = 'Test\Entity';
        $version = 'latest';
        $requestType = new RequestType([RequestType::REST]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->attachmentAssociationHelper->expects(self::once())
            ->method('isAttachmentAssociationEnabled')
            ->with($entityClass)
            ->willReturn(false);

        self::assertNull(
            $this->attachmentAssociationProvider->getAttachmentAssociationName($entityClass, $version, $requestType)
        );
        // test memory cache
        self::assertNull(
            $this->attachmentAssociationProvider->getAttachmentAssociationName($entityClass, $version, $requestType)
        );
    }

    public function testGetAttachmentAssociationNameForNotManageableEntity(): void
    {
        $entityClass = 'Test\Entity';
        $version = 'latest';
        $requestType = new RequestType([RequestType::REST]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(false);
        $this->attachmentAssociationHelper->expects(self::never())
            ->method('isAttachmentAssociationEnabled');

        self::assertNull(
            $this->attachmentAssociationProvider->getAttachmentAssociationName($entityClass, $version, $requestType)
        );
        // test memory cache
        self::assertNull(
            $this->attachmentAssociationProvider->getAttachmentAssociationName($entityClass, $version, $requestType)
        );
    }
}
