<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Tools;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommentAssociationHelperTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private CommentAssociationHelper $commentAssociationHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->commentAssociationHelper = new CommentAssociationHelper($this->configManager);
    }

    public function testIsCommentAssociationEnabledForNotConfigurableEntity(): void
    {
        $entityClass = 'Test\Entity';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects($this->never())
            ->method('getEntityConfig');

        $this->assertFalse(
            $this->commentAssociationHelper->isCommentAssociationEnabled($entityClass)
        );
    }

    public function testIsCommentAssociationEnabledForDisabledAssociation(): void
    {
        $entityClass = 'Test\Entity';

        $config = new Config(new EntityConfigId('comment', $entityClass));

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('comment', $entityClass)
            ->willReturn($config);

        $this->assertFalse(
            $this->commentAssociationHelper->isCommentAssociationEnabled($entityClass)
        );
    }

    public function testIsCommentAssociationEnabledForEnabledAssociation(): void
    {
        $entityClass = 'Test\Entity';

        $config = new Config(new EntityConfigId('comment', $entityClass));
        $config->set('enabled', true);

        $associationName = ExtendHelper::buildAssociationName($entityClass);
        $associationConfig = new Config(
            new FieldConfigId('extend', Comment::class, $associationName)
        );
        $associationConfig->set('is_extend', true);
        $associationConfig->set('state', ExtendScope::STATE_ACTIVE);

        $this->configManager->expects($this->exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [$entityClass, null, true],
                [Comment::class, $associationName, true],
            ]);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('comment', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', Comment::class, $associationName)
            ->willReturn($associationConfig);

        $this->assertTrue(
            $this->commentAssociationHelper->isCommentAssociationEnabled($entityClass)
        );
    }

    public function testIsCommentAssociationEnabledForEnabledButNotAccessibleAssociation(): void
    {
        $entityClass = 'Test\Entity';

        $config = new Config(new EntityConfigId('comment', $entityClass));
        $config->set('enabled', true);

        $associationName = ExtendHelper::buildAssociationName($entityClass);
        $associationConfig = new Config(
            new FieldConfigId('extend', Comment::class, $associationName)
        );
        $associationConfig->set('is_extend', true);
        $associationConfig->set('state', ExtendScope::STATE_NEW);

        $this->configManager->expects($this->exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [$entityClass, null, true],
                [Comment::class, $associationName, true],
            ]);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('comment', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', Comment::class, $associationName)
            ->willReturn($associationConfig);

        $this->assertFalse(
            $this->commentAssociationHelper->isCommentAssociationEnabled($entityClass)
        );
    }

    public function testIsCommentAssociationEnabledForEnabledButNotAccessibleAssociationButWithAccessibleFalse(): void
    {
        $entityClass = 'Test\Entity';

        $config = new Config(new EntityConfigId('comment', $entityClass));
        $config->set('enabled', true);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('comment', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->never())
            ->method('getFieldConfig');

        $this->assertTrue(
            $this->commentAssociationHelper->isCommentAssociationEnabled($entityClass, false)
        );
    }
}
