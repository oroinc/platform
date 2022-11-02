<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Api;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CommentBundle\Api\CommentAssociationProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class CommentAssociationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CommentAssociationProvider */
    private $commentAssociationProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->commentAssociationProvider = new CommentAssociationProvider(
            $this->doctrineHelper,
            $this->configManager
        );
    }

    public function testGetCommentAssociationNameForEntityWithEnabledComments(): void
    {
        $entityClass = 'Test\Entity';
        $version = 'latest';
        $requestType = new RequestType([RequestType::REST]);

        $entityConfig = $this->createMock(ConfigInterface::class);
        $entityConfig->expects(self::once())
            ->method('is')
            ->with('enabled')
            ->willReturn(true);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('comment', $entityClass)
            ->willReturn($entityConfig);

        $expected = ExtendHelper::buildAssociationName($entityClass);

        self::assertSame(
            $expected,
            $this->commentAssociationProvider->getCommentAssociationName($entityClass, $version, $requestType)
        );
        // test memory cache
        self::assertSame(
            $expected,
            $this->commentAssociationProvider->getCommentAssociationName($entityClass, $version, $requestType)
        );
    }

    public function testGetCommentAssociationNameForEntityWithDisabledComments(): void
    {
        $entityClass = 'Test\Entity';
        $version = 'latest';
        $requestType = new RequestType([RequestType::REST]);

        $entityConfig = $this->createMock(ConfigInterface::class);
        $entityConfig->expects(self::once())
            ->method('is')
            ->with('enabled')
            ->willReturn(false);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('comment', $entityClass)
            ->willReturn($entityConfig);

        self::assertNull(
            $this->commentAssociationProvider->getCommentAssociationName($entityClass, $version, $requestType)
        );
        // test memory cache
        self::assertNull(
            $this->commentAssociationProvider->getCommentAssociationName($entityClass, $version, $requestType)
        );
    }

    public function testGetCommentAssociationNameForNotConfigurableEntity(): void
    {
        $entityClass = 'Test\Entity';
        $version = 'latest';
        $requestType = new RequestType([RequestType::REST]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');

        self::assertNull(
            $this->commentAssociationProvider->getCommentAssociationName($entityClass, $version, $requestType)
        );
        // test memory cache
        self::assertNull(
            $this->commentAssociationProvider->getCommentAssociationName($entityClass, $version, $requestType)
        );
    }

    public function testGetCommentAssociationNameForNotManageableEntity(): void
    {
        $entityClass = 'Test\Entity';
        $version = 'latest';
        $requestType = new RequestType([RequestType::REST]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('hasConfig');
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');

        self::assertNull(
            $this->commentAssociationProvider->getCommentAssociationName($entityClass, $version, $requestType)
        );
        // test memory cache
        self::assertNull(
            $this->commentAssociationProvider->getCommentAssociationName($entityClass, $version, $requestType)
        );
    }
}
