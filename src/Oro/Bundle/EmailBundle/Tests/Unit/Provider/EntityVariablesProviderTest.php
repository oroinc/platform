<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\EntityVariablesProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class EntityVariablesProviderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_NAME = 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEntityForVariableProvider';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emailConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var EntityVariablesProvider */
    protected $provider;

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

        $this->provider = new EntityVariablesProvider(
            $translator,
            $this->emailConfigProvider,
            $this->entityConfigProvider
        );
    }

    protected function tearDown()
    {
        unset($this->emailConfigProvider);
        unset($this->entityConfigProvider);
        unset($this->provider);
    }

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

        $this->emailConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::TEST_ENTITY_NAME)
            ->will($this->returnValue(true));
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

        $result = $this->provider->getVariableDefinitions(self::TEST_ENTITY_NAME);
        $this->assertEquals(
            [
                'field1' => ['type' => 'string', 'label' => 'field1_label'],
                'field3' => ['type' => 'boolean', 'label' => 'field3_label'],
                'field5' => ['type' => 'string', 'label' => 'field5_label'],
            ],
            $result
        );
    }

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
        $this->emailConfigProvider->expects($this->exactly(2))
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_ENTITY_NAME, null, true],
                        [$entity2Class, null, true],
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

        $this->emailConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::TEST_ENTITY_NAME)
            ->will($this->returnValue(true));
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
        $this->emailConfigProvider->expects($this->exactly(2))
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_ENTITY_NAME, null, true],
                        [$entity2Class, null, true],
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
