<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Provider\EntityVariablesProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEntityForVariableProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\UIBundle\Formatter\FormatterManager;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityVariablesProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ENTITY_NAME = TestEntityForVariableProvider::class;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var FormatterManager|\PHPUnit\Framework\MockObject\MockObject */
    private $formatterManager;

    /** @var EntityVariablesProvider */
    private $provider;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->emailConfigProvider = $this->createMock(ConfigProvider::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->formatterManager = $this->createMock(FormatterManager::class);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['entity', $this->entityConfigProvider],
                ['email', $this->emailConfigProvider],
                ['extend', $this->extendConfigProvider],
            ]);

        $this->provider = new EntityVariablesProvider(
            $translator,
            $configManager,
            $this->doctrine,
            $this->formatterManager,
            (new InflectorFactory())->build()
        );
    }

    public function testGetVariableDefinitions()
    {
        $this->assertClassMetadataCalls();
        $this->assertEmailConfigProviderCalls();
        $this->assertFormatterCalls();

        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->willReturn(
                [
                    new EntityConfigId('entity', self::TEST_ENTITY_NAME),
                    new EntityConfigId('entity', TestUser::class),
                ]
            );
        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(\stdClass::class)
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [self::TEST_ENTITY_NAME, null, true],
                [TestUser::class, null, true],
            ]);

        $entity1ExtendConfig = new Config(new EntityConfigId('extend', self::TEST_ENTITY_NAME));
        $entity1ExtendConfig->set('is_extend', true);

        $entity2ExtendConfig = new Config(new EntityConfigId('extend', TestUser::class));
        $entity2ExtendConfig->set('is_extend', true);
        $this->extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [self::TEST_ENTITY_NAME, null, $entity1ExtendConfig],
                [TestUser::class, null, $entity2ExtendConfig],
            ]);

        $entity1field1EntityConfig = new Config(
            new FieldConfigId('entity', self::TEST_ENTITY_NAME, 'field1', 'datetime')
        );
        $entity1field1EntityConfig->set('label', 'field1_label');
        $entity1field2EntityConfig = new Config(
            new FieldConfigId('entity', self::TEST_ENTITY_NAME, 'field2', 'ref-one')
        );
        $entity1field2EntityConfig->set('label', 'field2_label');

        $entity2field1EntityConfig = new Config(new FieldConfigId('entity', TestUser::class, 'email', 'string'));
        $entity2field1EntityConfig->set('label', 'email_label');
        $this->entityConfigProvider->expects($this->exactly(3))
            ->method('getConfig')
            ->willReturnMap([
                [self::TEST_ENTITY_NAME, 'field1', $entity1field1EntityConfig],
                [self::TEST_ENTITY_NAME, 'field2', $entity1field2EntityConfig],
                [TestUser::class, 'email', $entity2field1EntityConfig],
            ]);

        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [
                self::TEST_ENTITY_NAME => [
                    'field1' => [
                        'type' => 'datetime',
                        'label' => 'field1_label',
                        'default_formatter' => 'format_date'
                    ],
                    'field2' => [
                        'type' => 'ref-one',
                        'label' => 'field2_label',
                        'related_entity_name' => \stdClass::class
                    ],
                ],
                TestUser::class => [
                    'email' => ['type' => 'string', 'label' => 'email_label'],
                ],
            ],
            $result
        );
    }

    public function testGetVariableGetters()
    {
        $entity1ExtendConfig = new Config(new EntityConfigId('extend', self::TEST_ENTITY_NAME));
        $entity1ExtendConfig->set('is_extend', true);

        $entity2ExtendConfig = new Config(new EntityConfigId('extend', TestUser::class));
        $entity2ExtendConfig->set('is_extend', true);

        $entity3Class = 'Extend\Entity\SomeEntity1';
        $entity3ExtendConfig = new Config(new EntityConfigId('extend', $entity3Class));
        $entity3ExtendConfig->set('is_extend', true);
        $entity3ExtendConfig->set('state', ExtendScope::STATE_NEW);

        $entity4Class = 'Extend\Entity\SomeEntity2';
        $entity4ExtendConfig = new Config(new EntityConfigId('extend', $entity4Class));
        $entity4ExtendConfig->set('is_extend', true);
        $entity4ExtendConfig->set('is_deleted', true);

        $this->assertClassMetadataCalls();
        $this->assertEmailConfigProviderCalls();
        $this->assertFormatterCalls();

        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->willReturn(
                [
                    new EntityConfigId('entity', self::TEST_ENTITY_NAME),
                    new EntityConfigId('entity', TestUser::class),
                    new EntityConfigId('entity', $entity3Class),
                    new EntityConfigId('entity', $entity4Class),
                ]
            );

        $this->extendConfigProvider->expects($this->exactly(4))
            ->method('hasConfig')
            ->willReturnMap([
                [self::TEST_ENTITY_NAME, null, true],
                [TestUser::class, null, true],
                [$entity3Class, null, true],
                [$entity4Class, null, true],
            ]);
        $this->extendConfigProvider->expects($this->exactly(4))
            ->method('getConfig')
            ->willReturnMap([
                [self::TEST_ENTITY_NAME, null, $entity1ExtendConfig],
                [TestUser::class, null, $entity2ExtendConfig],
                [$entity3Class, null, $entity3ExtendConfig],
                [$entity4Class, null, $entity4ExtendConfig],
            ]);

        $result = $this->provider->getVariableGetters();
        $this->assertEquals(
            [
                self::TEST_ENTITY_NAME => [
                    'field1' => [
                        'default_formatter' => 'format_date',
                        'property_path' => 'getField1'
                    ],
                    'field2' => [
                        'related_entity_name' => \stdClass::class,
                        'property_path' => 'getField2'
                    ]
                ],
                TestUser::class => [
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

    private function assertClassMetadataCalls(): void
    {
        $classMetadata1 = $this->createMock(ClassMetadata::class);
        $classMetadata1->expects($this->exactly(2))
            ->method('hasAssociation')
            ->willReturnMap([
                ['field1', false],
                ['field2', true],
            ]);
        $classMetadata1->expects($this->once())
            ->method('getAssociationTargetClass')
            ->willReturn(\stdClass::class);
        $classMetadata2 = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [self::TEST_ENTITY_NAME, $classMetadata1],
                [TestUser::class, $classMetadata2],
            ]);
        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturn($em);
    }

    private function assertEmailConfigProviderCalls(): void
    {
        $entity1field1Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field1', 'datetime'));
        $entity1field1Config->set('available_in_template', true);
        $entity1field2Config = new Config(new FieldConfigId('email', self::TEST_ENTITY_NAME, 'field2', 'ref-one'));
        $entity1field2Config->set('available_in_template', true);

        $entity2field1Config = new Config(new FieldConfigId('email', TestUser::class, 'email', 'string'));
        $entity2field1Config->set('available_in_template', true);
        $this->emailConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->willReturnMap([
                [self::TEST_ENTITY_NAME, false, [$entity1field1Config, $entity1field2Config]],
                [TestUser::class, false, [$entity2field1Config]],
            ]);
    }

    private function assertFormatterCalls(): void
    {
        $this->formatterManager->expects($this->any())
            ->method('guessFormatter')
            ->willReturnMap([
                ['datetime', 'format_date'],
                ['string', null],
                ['ref-one', null]
            ]);
    }
}
