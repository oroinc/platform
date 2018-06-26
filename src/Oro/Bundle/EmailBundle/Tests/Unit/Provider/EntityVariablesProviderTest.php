<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\EntityVariablesProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class EntityVariablesProviderTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ENTITY_NAME = 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEntityForVariableProvider';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $emailConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $extendConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var EntityVariablesProvider */
    protected $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $formatterManager;

    protected function setUp()
    {
        $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->emailConfigProvider  = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formatterManager = $this->getMockBuilder('Oro\Bundle\UIBundle\Formatter\FormatterManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['entity', $this->entityConfigProvider],
                        ['email', $this->emailConfigProvider],
                        ['extend', $this->extendConfigProvider],
                    ]
                )
            );

        $this->provider = new EntityVariablesProvider(
            $translator,
            $configManager,
            $this->doctrine,
            $this->formatterManager
        );
    }

    protected function tearDown()
    {
        unset($this->emailConfigProvider);
        unset($this->entityConfigProvider);
        unset($this->provider);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetVariableDefinitionsForOneEntity()
    {
        $field1Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field1', 'string'));
        $field1Config->set('available_in_template', true);
        $field2Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field2', 'integer'));
        $field3Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field3', 'boolean'));
        $field3Config->set('available_in_template', true);
        $field4Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field4', 'string'));
        $field4Config->set('available_in_template', true);
        $field5Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field5', 'string'));
        $field5Config->set('available_in_template', true);

        $field1EntityConfig = new Config(new FieldConfigId('entity', self::TEST_ENTITY_NAME, 'field1', 'string'));
        $field1EntityConfig->set('label', 'field1_label');
        $field3EntityConfig = new Config(new FieldConfigId('entity', self::TEST_ENTITY_NAME, 'field3', 'boolean'));
        $field3EntityConfig->set('label', 'field3_label');
        $field5EntityConfig = new Config(new FieldConfigId('entity', self::TEST_ENTITY_NAME, 'field5', 'string'));
        $field5EntityConfig->set('label', 'field5_label');

        $entityExtendConfig = new Config(new EntityConfigId('extend', self::TEST_ENTITY_NAME));
        $entityExtendConfig->set('is_extend', true);

        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $em            = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(self::TEST_ENTITY_NAME)
            ->will($this->returnValue($classMetadata));
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_NAME)
            ->will($this->returnValue($em));

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::TEST_ENTITY_NAME)
            ->will($this->returnValue(true));
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TEST_ENTITY_NAME)
            ->will($this->returnValue($entityExtendConfig));
        $this->emailConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with(self::TEST_ENTITY_NAME)
            ->will(
                $this->returnValue(
                    [$field1Config, $field2Config, $field3Config, $field4Config, $field5Config]
                )
            );

        $this->entityConfigProvider->expects($this->exactly(3))
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_ENTITY_NAME, 'field1', $field1EntityConfig],
                        [self::TEST_ENTITY_NAME, 'field3', $field3EntityConfig],
                        [self::TEST_ENTITY_NAME, 'field5', $field5EntityConfig],
                    ]
                )
            );

        $classMetadata->expects($this->exactly(3))
            ->method('hasAssociation')
            ->will(
                $this->returnValueMap(
                    [
                        ['field1', false],
                        ['field3', true],
                        ['field5', true],
                    ]
                )
            );
        $classMetadata->expects($this->exactly(2))
            ->method('getAssociationTargetClass')
            ->will(
                $this->returnValueMap(
                    [
                        ['field3', 'RelatedEntity3'],
                        ['field5', 'RelatedEntity5'],
                    ]
                )
            );
        $this->entityConfigProvider->expects($this->exactly(2))
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        ['RelatedEntity3', null, false],
                        ['RelatedEntity5', null, true],
                    ]
                )
            );

        $result = $this->provider->getVariableDefinitions(self::TEST_ENTITY_NAME);
        $this->assertEquals(
            [
                'field1' => ['type' => 'string', 'label' => 'field1_label'],
                'field3' => ['type' => 'boolean', 'label' => 'field3_label'],
                'field5' => [
                    'type'                => 'string',
                    'label'               => 'field5_label',
                    'related_entity_name' => 'RelatedEntity5'
                ],
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetVariableDefinitionsForAllEntities()
    {
        $entity1field1Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field1', 'string'));
        $entity1field1Config->set('available_in_template', true);
        $entity1field1EntityConfig = new Config(
            new FieldConfigId('entity', self::TEST_ENTITY_NAME, 'field1', 'string')
        );
        $entity1field1EntityConfig->set('label', 'field1_label');

        $entity2Class        = 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser';
        $entity2field1Config = new Config(new FieldConfigId('email', $entity2Class, 'email', 'string'));
        $entity2field1Config->set('available_in_template', true);
        $entity2field1EntityConfig = new Config(new FieldConfigId('entity', $entity2Class, 'email', 'string'));
        $entity2field1EntityConfig->set('label', 'email_label');

        $entity1ExtendConfig = new Config(new EntityConfigId('extend', self::TEST_ENTITY_NAME));
        $entity1ExtendConfig->set('is_extend', true);

        $entity2ExtendConfig = new Config(new EntityConfigId('extend', $entity2Class));
        $entity2ExtendConfig->set('is_extend', true);

        $classMetadata1 = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata2 = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $em             = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_ENTITY_NAME, $classMetadata1],
                        [$entity2Class, $classMetadata2],
                    ]
                )
            );
        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_ENTITY_NAME, $em],
                        [$entity2Class, $em],
                    ]
                )
            );

        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->will(
                $this->returnValue(
                    [
                        new EntityConfigId('entity', self::TEST_ENTITY_NAME),
                        new EntityConfigId('entity', $entity2Class),
                    ]
                )
            );
        $this->extendConfigProvider->expects($this->exactly(2))
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_ENTITY_NAME, null, true],
                        [$entity2Class, null, true],
                    ]
                )
            );
        $this->extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_ENTITY_NAME, null, $entity1ExtendConfig],
                        [$entity2Class, null, $entity2ExtendConfig],
                    ]
                )
            );
        $this->emailConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_ENTITY_NAME, false, [$entity1field1Config]],
                        [$entity2Class, false, [$entity2field1Config]],
                    ]
                )
            );

        $this->entityConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_ENTITY_NAME, 'field1', $entity1field1EntityConfig],
                        [$entity2Class, 'email', $entity2field1EntityConfig],
                    ]
                )
            );

        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [
                self::TEST_ENTITY_NAME => [
                    'field1' => ['type' => 'string', 'label' => 'field1_label'],
                ],
                $entity2Class          => [
                    'email' => ['type' => 'string', 'label' => 'email_label'],
                ],
            ],
            $result
        );
    }

    public function testGetVariableGettersForOneEntity()
    {
        $field1Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field1', 'string'));
        $field1Config->set('available_in_template', true);
        $field2Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field2', 'integer'));
        $field3Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field3', 'boolean'));
        $field3Config->set('available_in_template', true);
        $field4Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field4', 'string'));
        $field4Config->set('available_in_template', true);
        $field5Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field5', 'string'));
        $field5Config->set('available_in_template', true);

        $entityExtendConfig = new Config(new EntityConfigId('extend', self::TEST_ENTITY_NAME));
        $entityExtendConfig->set('is_extend', true);

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::TEST_ENTITY_NAME)
            ->will($this->returnValue(true));
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TEST_ENTITY_NAME)
            ->will($this->returnValue($entityExtendConfig));
        $this->emailConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with(self::TEST_ENTITY_NAME)
            ->will(
                $this->returnValue(
                    [$field1Config, $field2Config, $field3Config, $field4Config, $field5Config]
                )
            );

        $result = $this->provider->getVariableGetters(self::TEST_ENTITY_NAME);
        $this->assertEquals(
            [
                'field1' => 'getField1',
                'field3' => 'isField3',
                'field5' => null,
            ],
            $result
        );
    }

    public function testGetVariableGettersForAllEntities()
    {
        $entity1field1Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field1', 'string'));
        $entity1field1Config->set('available_in_template', true);

        $entity2Class        = 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser';
        $entity2field1Config = new Config(new FieldConfigId('email', $entity2Class, 'email', 'string'));
        $entity2field1Config->set('available_in_template', true);

        $entity1ExtendConfig = new Config(new EntityConfigId('extend', self::TEST_ENTITY_NAME));
        $entity1ExtendConfig->set('is_extend', true);

        $entity2ExtendConfig = new Config(new EntityConfigId('extend', $entity2Class));
        $entity2ExtendConfig->set('is_extend', true);

        $entity3Class        = 'Extend\Entity\SomeEntity1';
        $entity3ExtendConfig = new Config(new EntityConfigId('extend', $entity3Class));
        $entity3ExtendConfig->set('is_extend', true);
        $entity3ExtendConfig->set('state', ExtendScope::STATE_NEW);

        $entity4Class        = 'Extend\Entity\SomeEntity2';
        $entity4ExtendConfig = new Config(new EntityConfigId('extend', $entity4Class));
        $entity4ExtendConfig->set('is_extend', true);
        $entity4ExtendConfig->set('is_deleted', true);

        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->will(
                $this->returnValue(
                    [
                        new EntityConfigId('entity', self::TEST_ENTITY_NAME),
                        new EntityConfigId('entity', $entity2Class),
                        new EntityConfigId('entity', $entity3Class),
                        new EntityConfigId('entity', $entity4Class),
                    ]
                )
            );

        $this->emailConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_ENTITY_NAME, false, [$entity1field1Config]],
                        [$entity2Class, false, [$entity2field1Config]],
                    ]
                )
            );

        $this->extendConfigProvider->expects($this->exactly(4))
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_ENTITY_NAME, null, true],
                        [$entity2Class, null, true],
                        [$entity3Class, null, true],
                        [$entity4Class, null, true],
                    ]
                )
            );
        $this->extendConfigProvider->expects($this->exactly(4))
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_ENTITY_NAME, null, $entity1ExtendConfig],
                        [$entity2Class, null, $entity2ExtendConfig],
                        [$entity3Class, null, $entity3ExtendConfig],
                        [$entity4Class, null, $entity4ExtendConfig],
                    ]
                )
            );

        $result = $this->provider->getVariableGetters();
        $this->assertEquals(
            [
                self::TEST_ENTITY_NAME => [
                    'field1' => 'getField1',
                ],
                $entity2Class          => [
                    'email' => 'getEmail',
                ],
            ],
            $result
        );
    }
}
