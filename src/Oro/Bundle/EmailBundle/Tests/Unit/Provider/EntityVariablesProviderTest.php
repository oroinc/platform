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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetVariableDefinitions()
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

    public function testGetVariableGetters()
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

    public function testGetVariableProcessors()
    {
        self::assertSame([], $this->provider->getVariableProcessors(self::TEST_ENTITY_NAME));
    }
}
