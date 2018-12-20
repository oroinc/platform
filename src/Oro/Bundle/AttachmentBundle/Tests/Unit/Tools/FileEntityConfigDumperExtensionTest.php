<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Tools\FileEntityConfigDumperExtension;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class FileEntityConfigDumperExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $relationBuilder;

    /** @var FileEntityConfigDumperExtension */
    protected $extension;

    public function setUp()
    {
        $this->configManager   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->relationBuilder = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new FileEntityConfigDumperExtension(
            $this->configManager,
            $this->relationBuilder
        );
    }

    public function testSupportsPreUpdate()
    {
        $this->assertTrue(
            $this->extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE)
        );
    }

    public function testSupportsPostUpdate()
    {
        $this->assertFalse(
            $this->extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE)
        );
    }

    /**
     * @dataProvider preUpdateProvider
     */
    public function testPreUpdate($fieldType)
    {
        $entityClass = 'Test\Entity';
        $fieldName   = 'test_field';

        $entityConfig = new Config(new EntityConfigId('extend', $entityClass));
        $entityConfig->set('is_extend', true);

        $fieldConfig = new Config(new FieldConfigId('extend', $entityClass, $fieldName, $fieldType));
        $fieldConfig->set('state', ExtendScope::STATE_NEW);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnValueMap(
                    [
                        [null, false, [$entityConfig]],
                        [$entityClass, false, [$fieldConfig]],
                    ]
                )
            );

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $relationKey = 'test_relation_key';
        $this->relationBuilder->expects($this->once())
            ->method('addManyToOneRelation')
            ->with(
                $this->identicalTo($entityConfig),
                'Oro\Bundle\AttachmentBundle\Entity\File',
                $fieldName,
                'id',
                [
                    'extend'       => [
                        'cascade' => ['persist']
                    ],
                    'importexport' => [
                        'process_as_scalar' => true
                    ]
                ],
                $fieldType
            )
            ->will($this->returnValue($relationKey));

        $this->extension->preUpdate();
    }

    /**
     * @dataProvider preUpdateProvider
     */
    public function testPreUpdateWithCascade($fieldType)
    {
        $entityClass = 'Test\Entity';
        $fieldName   = 'test_field';

        $entityConfig = new Config(new EntityConfigId('extend', $entityClass));
        $entityConfig->set('is_extend', true);

        $fieldConfig = new Config(new FieldConfigId('extend', $entityClass, $fieldName, $fieldType));
        $fieldConfig->set('state', ExtendScope::STATE_NEW);
        $fieldConfig->set('cascade', ['persist', 'remove']);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnValueMap(
                    [
                        [null, false, [$entityConfig]],
                        [$entityClass, false, [$fieldConfig]],
                    ]
                )
            );

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $relationKey = 'test_relation_key';
        $this->relationBuilder->expects($this->once())
            ->method('addManyToOneRelation')
            ->with(
                $this->identicalTo($entityConfig),
                'Oro\Bundle\AttachmentBundle\Entity\File',
                $fieldName,
                'id',
                [
                    'extend'       => [
                        'cascade' => ['persist', 'remove']
                    ],
                    'importexport' => [
                        'process_as_scalar' => true
                    ]
                ],
                $fieldType
            )
            ->will($this->returnValue($relationKey));

        $this->extension->preUpdate();
    }

    public function preUpdateProvider()
    {
        return [
            ['file'],
            ['image'],
        ];
    }

    public function testPreUpdateForNotSupportedFieldType()
    {
        $entityClass = 'Test\Entity';
        $fieldName   = 'test_field';
        $fieldType   = 'manyToOne';

        $entityConfig = new Config(new EntityConfigId('extend', $entityClass));
        $entityConfig->set('is_extend', true);

        $fieldConfig = new Config(new FieldConfigId('extend', $entityClass, $fieldName, $fieldType));
        $fieldConfig->set('state', ExtendScope::STATE_NEW);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnValueMap(
                    [
                        [null, false, [$entityConfig]],
                        [$entityClass, false, [$fieldConfig]],
                    ]
                )
            );

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $this->relationBuilder->expects($this->never())
            ->method('addManyToOneRelation');

        $this->extension->preUpdate();
    }

    public function testPreUpdateForFieldToBeDeleted()
    {
        $entityClass = 'Test\Entity';
        $fieldName   = 'test_field';
        $fieldType   = 'file';

        $entityConfig = new Config(new EntityConfigId('extend', $entityClass));
        $entityConfig->set('is_extend', true);

        $fieldConfig = new Config(new FieldConfigId('extend', $entityClass, $fieldName, $fieldType));
        $fieldConfig->set('state', ExtendScope::STATE_DELETE);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnValueMap(
                    [
                        [null, false, [$entityConfig]],
                        [$entityClass, false, [$fieldConfig]],
                    ]
                )
            );

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $this->relationBuilder->expects($this->never())
            ->method('addManyToOneRelation');

        $this->extension->preUpdate();
    }

    public function testPreUpdateForNotExtendedEntity()
    {
        $entityClass = 'Test\Entity';
        $fieldName   = 'test_field';
        $fieldType   = 'file';

        $entityConfig = new Config(new EntityConfigId('extend', $entityClass));

        $fieldConfig = new Config(new FieldConfigId('extend', $entityClass, $fieldName, $fieldType));
        $fieldConfig->set('state', ExtendScope::STATE_NEW);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with(null)
            ->will($this->returnValue([$entityConfig]));

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $this->relationBuilder->expects($this->never())
            ->method('addManyToOneRelation');

        $this->extension->preUpdate();
    }
}
