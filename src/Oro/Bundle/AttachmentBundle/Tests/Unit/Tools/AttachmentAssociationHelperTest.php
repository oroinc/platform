<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AttachmentAssociationHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var AttachmentAssociationHelper */
    protected $attachmentAssociationHelper;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachmentAssociationHelper = new AttachmentAssociationHelper($this->configManager);
    }

    public function testIsAttachmentAssociationEnabledForNotConfigurableEntity()
    {
        $entityClass = 'Test\Entity';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects($this->never())
            ->method('getEntityConfig');

        $this->assertFalse(
            $this->attachmentAssociationHelper->isAttachmentAssociationEnabled($entityClass)
        );
    }

    public function testIsAttachmentAssociationEnabledForDisabledAssociation()
    {
        $entityClass = 'Test\Entity';

        $config = new Config(new EntityConfigId('attachment', $entityClass));

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('attachment', $entityClass)
            ->willReturn($config);

        $this->assertFalse(
            $this->attachmentAssociationHelper->isAttachmentAssociationEnabled($entityClass)
        );
    }

    public function testIsAttachmentAssociationEnabledForEnabledAssociation()
    {
        $entityClass = 'Test\Entity';

        $config = new Config(new EntityConfigId('attachment', $entityClass));
        $config->set('enabled', true);

        $associationName   = ExtendHelper::buildAssociationName($entityClass);
        $associationConfig = new Config(
            new FieldConfigId('extend', AttachmentAssociationHelper::ATTACHMENT_ENTITY, $associationName)
        );
        $associationConfig->set('is_extend', true);
        $associationConfig->set('state', ExtendScope::STATE_ACTIVE);

        $this->configManager->expects($this->exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [AttachmentAssociationHelper::ATTACHMENT_ENTITY, $associationName, true],
                ]
            );
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('attachment', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', AttachmentAssociationHelper::ATTACHMENT_ENTITY, $associationName)
            ->willReturn($associationConfig);

        $this->assertTrue(
            $this->attachmentAssociationHelper->isAttachmentAssociationEnabled($entityClass)
        );
    }

    public function testIsAttachmentAssociationEnabledForEnabledButNotAccessibleAssociation()
    {
        $entityClass = 'Test\Entity';

        $config = new Config(new EntityConfigId('attachment', $entityClass));
        $config->set('enabled', true);

        $associationName   = ExtendHelper::buildAssociationName($entityClass);
        $associationConfig = new Config(
            new FieldConfigId('extend', AttachmentAssociationHelper::ATTACHMENT_ENTITY, $associationName)
        );
        $associationConfig->set('is_extend', true);
        $associationConfig->set('state', ExtendScope::STATE_NEW);

        $this->configManager->expects($this->exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [AttachmentAssociationHelper::ATTACHMENT_ENTITY, $associationName, true],
                ]
            );
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('attachment', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', AttachmentAssociationHelper::ATTACHMENT_ENTITY, $associationName)
            ->willReturn($associationConfig);

        $this->assertFalse(
            $this->attachmentAssociationHelper->isAttachmentAssociationEnabled($entityClass)
        );
    }

    public function testIsAttachmentAssociationEnabledForEnabledButNotAccessibleAssociationButWithAccessibleFalse()
    {
        $entityClass = 'Test\Entity';

        $config = new Config(new EntityConfigId('attachment', $entityClass));
        $config->set('enabled', true);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('attachment', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->never())
            ->method('getFieldConfig');

        $this->assertTrue(
            $this->attachmentAssociationHelper->isAttachmentAssociationEnabled($entityClass, false)
        );
    }
}
