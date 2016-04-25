<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\NoteBundle\Tools\NoteAssociationHelper;

class NoteAssociationHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var NoteAssociationHelper */
    protected $noteAssociationHelper;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->noteAssociationHelper = new NoteAssociationHelper($this->configManager);
    }

    public function testIsNoteAssociationEnabledForNotConfigurableEntity()
    {
        $entityClass = 'Test\Entity';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects($this->never())
            ->method('getEntityConfig');

        $this->assertFalse(
            $this->noteAssociationHelper->isNoteAssociationEnabled($entityClass)
        );
    }

    public function testIsNoteAssociationEnabledForDisabledAssociation()
    {
        $entityClass = 'Test\Entity';

        $config = new Config(new EntityConfigId('note', $entityClass));

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('note', $entityClass)
            ->willReturn($config);

        $this->assertFalse(
            $this->noteAssociationHelper->isNoteAssociationEnabled($entityClass)
        );
    }

    public function testIsNoteAssociationEnabledForEnabledAssociation()
    {
        $entityClass = 'Test\Entity';

        $config = new Config(new EntityConfigId('note', $entityClass));
        $config->set('enabled', true);

        $associationName   = ExtendHelper::buildAssociationName($entityClass);
        $associationConfig = new Config(
            new FieldConfigId('extend', NoteAssociationHelper::NOTE_ENTITY, $associationName)
        );
        $associationConfig->set('is_extend', true);
        $associationConfig->set('state', ExtendScope::STATE_ACTIVE);

        $this->configManager->expects($this->exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [NoteAssociationHelper::NOTE_ENTITY, $associationName, true],
                ]
            );
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('note', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', NoteAssociationHelper::NOTE_ENTITY, $associationName)
            ->willReturn($associationConfig);

        $this->assertTrue(
            $this->noteAssociationHelper->isNoteAssociationEnabled($entityClass)
        );
    }

    public function testIsNoteAssociationEnabledForEnabledButNotAccessibleAssociation()
    {
        $entityClass = 'Test\Entity';

        $config = new Config(new EntityConfigId('note', $entityClass));
        $config->set('enabled', true);

        $associationName   = ExtendHelper::buildAssociationName($entityClass);
        $associationConfig = new Config(
            new FieldConfigId('extend', NoteAssociationHelper::NOTE_ENTITY, $associationName)
        );
        $associationConfig->set('is_extend', true);
        $associationConfig->set('state', ExtendScope::STATE_NEW);

        $this->configManager->expects($this->exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [NoteAssociationHelper::NOTE_ENTITY, $associationName, true],
                ]
            );
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('note', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', NoteAssociationHelper::NOTE_ENTITY, $associationName)
            ->willReturn($associationConfig);

        $this->assertFalse(
            $this->noteAssociationHelper->isNoteAssociationEnabled($entityClass)
        );
    }

    public function testIsNoteAssociationEnabledForEnabledButNotAccessibleAssociationButWithAccessibleFalse()
    {
        $entityClass = 'Test\Entity';

        $config = new Config(new EntityConfigId('note', $entityClass));
        $config->set('enabled', true);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('note', $entityClass)
            ->willReturn($config);
        $this->configManager->expects($this->never())
            ->method('getFieldConfig');

        $this->assertTrue(
            $this->noteAssociationHelper->isNoteAssociationEnabled($entityClass, false)
        );
    }
}
