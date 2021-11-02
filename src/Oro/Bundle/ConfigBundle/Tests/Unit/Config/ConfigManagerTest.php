<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\CacheBundle\Provider\MemoryCache;
use Oro\Bundle\ConfigBundle\Config\ConfigDefinitionImmutableBag;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\ConfigBundle\Event\ConfigGetEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ConfigBundle\Provider\Value\ValueProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager */
    private $manager;

    /** @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var MemoryCache|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCache;

    /** @var GlobalScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $globalScopeManager;

    /** @var GlobalScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userScopeManager;

    /** @var ValueProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultValueProvider;

    private array $settings = [
        'oro_user' => [
            'greeting' => [
                'value' => true,
                'type' => 'boolean',
            ],
            'level' => [
                'value' => 20,
                'type' => 'scalar',
            ],
        ],
        'oro_test' => [
            'anysetting' => [
                'value' => 'anyvalue',
                'type' => 'scalar',
            ],
            'servicestring' => [
                'value' => '@oro_config.default_value_provider',
            ],
            'emptystring' => [
                'value' => '',
                'type' => 'scalar',
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->defaultValueProvider = $this->createMock(ValueProviderInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->memoryCache = $this->getMockBuilder(MemoryCache::class)
            ->onlyMethods(['deleteAll'])
            ->getMock();

        $this->globalScopeManager = $this->createMock(GlobalScopeManager::class);
        $this->userScopeManager = $this->createMock(GlobalScopeManager::class);

        $this->settings['oro_test']['servicestring']['value'] = $this->defaultValueProvider;

        $this->manager = new ConfigManager(
            'user',
            new ConfigDefinitionImmutableBag($this->settings),
            $this->dispatcher,
            $this->memoryCache
        );
        $this->manager->addManager('user', $this->userScopeManager);
        $this->manager->addManager('global', $this->globalScopeManager);
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testSet(object|int|null $scopeIdentifier)
    {
        $name = 'testName';
        $value = 'testValue';
        $this->userScopeManager->expects($this->once())
            ->method('set')
            ->with($name, $value, $scopeIdentifier);

        $this->manager->set($name, $value, $scopeIdentifier);
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testReset(object|int|null $scopeIdentifier)
    {
        $name = 'testName.testValue';
        $this->userScopeManager->expects($this->once())
            ->method('reset')
            ->with($name, $scopeIdentifier);

        $this->memoryCache->expects($this->once())
            ->method('deleteAll');

        $this->manager->reset($name, $scopeIdentifier);
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testFlush(object|int|null $scopeIdentifier, int $idValue)
    {
        $greetingKey = 'oro_user.greeting';
        $greetingValue = [
            'value' => 'updated value',
            'use_parent_scope_value' => false
        ];
        $changes = [
            $greetingKey => $greetingValue
        ];

        $expectedScopeIdentifier = $scopeIdentifier;
        if (null === $scopeIdentifier) {
            $expectedScopeIdentifier = $idValue;
            $this->userScopeManager->expects($this->once())
                ->method('getChangedScopeIdentifiers')
                ->willReturn([$idValue]);
        }

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($greetingKey)
            ->willReturn(['value' => 'old value']);

        $singleKeyBeforeEvent = new ConfigSettingsUpdateEvent($this->manager, $greetingValue);
        $beforeEvent = new ConfigSettingsUpdateEvent($this->manager, $changes);
        $afterEvent  = new ConfigUpdateEvent(
            [
                $greetingKey => ['old' => 'old value', 'new' => 'updated value']
            ],
            'user',
            $idValue
        );

        $loadEvent = new ConfigGetEvent($this->manager, $greetingKey, 'old value', false, $idValue);

        $this->userScopeManager->expects($this->once())
            ->method('getChanges')
            ->willReturn($changes);
        $this->userScopeManager->expects($this->any())
            ->method('resolveIdentifier')
            ->willReturn($idValue);

        $this->userScopeManager->expects($this->once())
            ->method('save')
            ->with($changes, $expectedScopeIdentifier)
            ->willReturn([
                [$greetingKey => 'updated value'],
                []
            ]);

        $this->dispatcher->expects($this->exactly(5))
            ->method('dispatch')
            ->withConsecutive(
                [$loadEvent, ConfigGetEvent::NAME],
                [$loadEvent, ConfigGetEvent::NAME . '.' . $greetingKey],
                [$singleKeyBeforeEvent, ConfigSettingsUpdateEvent::BEFORE_SAVE . '.' . $greetingKey],
                [$beforeEvent, ConfigSettingsUpdateEvent::BEFORE_SAVE],
                [$afterEvent, ConfigUpdateEvent::EVENT_NAME]
            );

        $this->manager->flush($scopeIdentifier);
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testSave(object|int|null $scopeIdentifier, int $idValue)
    {
        $greetingKey = 'oro_user.greeting';
        $levelKey = 'oro_user.level';

        $data = [
            'oro_user___greeting' => [
                'value'                  => 'updated value',
                'use_parent_scope_value' => false
            ],
            'oro_user___unknown'  => [
                'value'                  => 'some value',
                'use_parent_scope_value' => false
            ],
            'oro_user___level'    => [
                'use_parent_scope_value' => true
            ]
        ];

        $greetingsValue = [
            'value'                  => 'updated value',
            'use_parent_scope_value' => false
        ];
        $levelValue = [
            'use_parent_scope_value' => true
        ];
        $normalizedData = [
            $greetingKey => $greetingsValue,
            $levelKey    => $levelValue,
        ];

        $this->userScopeManager->expects($this->any())
            ->method('resolveIdentifier')
            ->willReturn($idValue);

        $this->userScopeManager->expects($this->exactly(2))
            ->method('getSettingValue')
            ->willReturnMap([
                [$greetingKey, true, $scopeIdentifier, true, ['value' => 'old value']],
                [$levelKey, true, $scopeIdentifier, true, ['value' => 2000]]
            ]);

        $singleKeyGreetingEvent = new ConfigSettingsUpdateEvent($this->manager, $greetingsValue);
        $singleKeyLevelEvent = new ConfigSettingsUpdateEvent($this->manager, $levelValue);
        $beforeEvent = new ConfigSettingsUpdateEvent($this->manager, $normalizedData);
        $afterEvent = new ConfigUpdateEvent(
            [
                $greetingKey => ['old' => 'old value', 'new' => 'updated value'],
                $levelKey    => ['old' => 2000, 'new' => 20]
            ],
            'user',
            $idValue
        );

        $greetingOldValueLoadEvent = new ConfigGetEvent($this->manager, $greetingKey, 'old value', false, $idValue);
        $levelOldValueLoadEvent = new ConfigGetEvent($this->manager, $levelKey, '2000', false, $idValue);
        $levelNullValueLoadEvent = new ConfigGetEvent($this->manager, $levelKey, null, false, $idValue);

        $this->userScopeManager->expects($this->once())
            ->method('save')
            ->with($normalizedData)
            ->willReturn([
                [$greetingKey => 'updated value'],
                [$levelKey]
            ]);

        $this->dispatcher->expects($this->exactly(10))
            ->method('dispatch')
            ->withConsecutive(
                [$greetingOldValueLoadEvent, ConfigGetEvent::NAME],
                [$greetingOldValueLoadEvent, ConfigGetEvent::NAME . '.' . $greetingKey],
                [$singleKeyGreetingEvent, ConfigSettingsUpdateEvent::BEFORE_SAVE . '.' . $greetingKey],
                [$levelOldValueLoadEvent, ConfigGetEvent::NAME],
                [$levelOldValueLoadEvent, ConfigGetEvent::NAME . '.' . $levelKey],
                [$singleKeyLevelEvent, ConfigSettingsUpdateEvent::BEFORE_SAVE . '.' . $levelKey],
                [$beforeEvent, ConfigSettingsUpdateEvent::BEFORE_SAVE],
                [$levelNullValueLoadEvent, ConfigGetEvent::NAME],
                [$levelNullValueLoadEvent, ConfigGetEvent::NAME  . '.' . $levelKey],
                [$afterEvent, ConfigUpdateEvent::EVENT_NAME]
            );

        $this->memoryCache->expects($this->once())
            ->method('deleteAll');

        $this->manager->save($data, $scopeIdentifier);
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testCalculateChangeSet(object|int|null $scopeIdentifier)
    {
        $data = [];
        $this->userScopeManager->expects($this->once())
            ->method('calculateChangeSet')
            ->with($data, $scopeIdentifier);

        $this->manager->calculateChangeSet($data, $scopeIdentifier);
    }

    public function testReload()
    {
        $this->userScopeManager->expects($this->once())
            ->method('reload');

        $this->memoryCache->expects($this->once())
            ->method('deleteAll');

        $this->manager->reload();
    }

    /**
     * @dataProvider getFromParentParamProvider
     */
    public function testGetFromParentScope(string $parameterName, bool $full, mixed $expectedResult)
    {
        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName)
            ->willReturn(null);

        $this->globalScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName)
            ->willReturnCallback(function ($name) {
                if ($name === 'oro_test.someArrayValue') {
                    $value = [
                        'scope'                  => 'global',
                        'value'                  => ['foo' => 'bar'],
                        'use_parent_scope_value' => false
                    ];
                } else {
                    $value = ['scope' => 'global', 'value' => 1, 'use_parent_scope_value' => true];
                }

                return $value;
            });

        $this->assertEquals(
            $expectedResult,
            $this->manager->get($parameterName, false, $full)
        );
    }

    public function getFromParentParamProvider(): array
    {
        return [
            [
                'parameterName'  => 'oro_test.someValue',
                'full'           => false,
                'expectedResult' => 1,
            ],
            [
                'parameterName'  => 'oro_test.someValue',
                'full'           => true,
                'expectedResult' => ['scope' => 'global', 'value' => 1, 'use_parent_scope_value' => true],
            ],
            [
                'parameterName'  => 'oro_test.someArrayValue',
                'full'           => true,
                'expectedResult' => [
                    'scope'                  => 'global',
                    'value'                  => ['foo' => 'bar'],
                    'use_parent_scope_value' => true
                ]
            ],
            [
                'parameterName'  => 'oro_test.someArrayValue',
                'full'           => false,
                'expectedResult' => ['foo' => 'bar']
            ]
        ];
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testGet(object|int|null $scopeIdentifier)
    {
        $parameterName = 'oro_test.someValue';

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, true, $scopeIdentifier)
            ->willReturn(['value' => 2]);

        $this->globalScopeManager->expects($this->never())
            ->method('getSettingValue');

        $this->assertSame(2, $this->manager->get($parameterName, false, false, $scopeIdentifier));
        // check cache
        $this->assertSame(2, $this->manager->get($parameterName, false, false, $scopeIdentifier));
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testGetDefaultSettings(object|int|null $scopeIdentifier)
    {
        $parameterName = 'oro_test.anysetting';

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, true, $scopeIdentifier)
            ->willReturn(null);

        $this->globalScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, true, $scopeIdentifier)
            ->willReturn(null);

        $this->assertEquals('anyvalue', $this->manager->get($parameterName, false, false, $scopeIdentifier));
        // check cache
        $this->assertEquals('anyvalue', $this->manager->get($parameterName, false, false, $scopeIdentifier));
    }

    public function testGetEmptyValueSettings()
    {
        $parameterName = 'oro_test.emptystring';

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, true)
            ->willReturn(['value' => '']);

        $this->globalScopeManager->expects($this->never())
            ->method('getSettingValue');

        $this->assertSame('', $this->manager->get($parameterName));
        // check cache
        $this->assertSame('', $this->manager->get($parameterName));
    }

    public function testGetSettingsDefaultsMethodReturnNullForMissing()
    {
        $this->assertEquals(null, $this->manager->getSettingsDefaults('oro_test.null', true));
    }

    public function testGetSettingsDefaultsMethodReturnPlain()
    {
        $this->assertEquals('anyvalue', $this->manager->getSettingsDefaults('oro_test.anysetting'));
    }

    public function testGetSettingsDefaultsMethodReturnFull()
    {
        $this->assertEquals(
            [
                'value' => 'anyvalue',
                'type' => 'scalar',
            ],
            $this->manager->getSettingsDefaults('oro_test.anysetting', true)
        );
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testGetMergedWithParentValue(object|int|null $scopeIdentifier)
    {
        $parameterName = 'oro_test.anysetting';

        $this->globalScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, false, $scopeIdentifier)
            ->willReturn(['a' => 1, 'b' => 1, 'c' => 1]);

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, false, $scopeIdentifier)
            ->willReturn(['a' => null, 'b' => 2]);

        $this->assertEquals(
            [
                'a' => null,
                'b' => 2,
                'c' => 1,
            ],
            $this->manager->getMergedWithParentValue([], $parameterName, false, $scopeIdentifier)
        );
    }

    public function testGetValues()
    {
        $parameterName = 'oro_test.someValue';
        $entity1 = new \stdClass();
        $entity1->id = 1;
        $entity2 = new \stdClass();
        $entity2->id = 2;
        $entities = [$entity1, $entity2];

        $this->userScopeManager->expects($this->any())
            ->method('resolveIdentifier')
            ->willReturnMap([
                [$entity1, null],
                [$entity2, 55]
            ]);
        $this->globalScopeManager->expects($this->any())
            ->method('resolveIdentifier')
            ->willReturnMap([
                [$entity1, 33],
            ]);

        $this->userScopeManager->expects($this->any())
            ->method('getScopeIdFromEntity')
            ->willReturnCallback(function (\stdClass $scope) {
                return $scope->id;
            });
        $this->globalScopeManager->expects($this->never())
            ->method('getScopeIdFromEntity');

        $this->userScopeManager->expects($this->exactly(2))
            ->method('getSettingValue')
            ->willReturnMap([
                [$parameterName, true, $entity1, false, ['value' => 'val1']],
                [$parameterName, true, $entity2, false, ['value' => 'val2']]
            ]);
        $this->globalScopeManager->expects($this->never())
            ->method('getSettingValue');

        $this->assertEquals([33 => 'val1', 55 => 'val2'], $this->manager->getValues($parameterName, $entities));
        // check cache
        $this->assertEquals([33 => 'val1', 55 => 'val2'], $this->manager->getValues($parameterName, $entities));
    }

    public function scopeIdentifierDataProvider(): array
    {
        return [
            [null, 1],
            [2, 2],
            [new \stdClass(), 123]
        ];
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testGetDefaultSettingsFromProvider(object|int|null $scopeIdentifier)
    {
        $parameterName = 'oro_test.servicestring';

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, true, $scopeIdentifier)
            ->willReturn(null);

        $this->globalScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, true, $scopeIdentifier)
            ->willReturn(null);

        $value = 1;

        $this->defaultValueProvider->expects(self::once())
            ->method('getValue')
            ->willReturn($value);

        $this->assertEquals($value, $this->manager->get($parameterName, false, false, $scopeIdentifier));
        // check cache
        $this->assertEquals($value, $this->manager->get($parameterName, false, false, $scopeIdentifier));
    }

    public function testDeleteScope()
    {
        $scopeIdentifier = 1;

        $this->userScopeManager->expects(self::once())
            ->method('deleteScope')
            ->with($scopeIdentifier);

        $this->manager->deleteScope($scopeIdentifier);
    }

    public function testDeleteScopeWithObjectAsIdentifier()
    {
        $scopeIdentifier = new \stdClass();

        $this->userScopeManager->expects(self::once())
            ->method('deleteScope')
            ->with($scopeIdentifier);

        $this->manager->deleteScope($scopeIdentifier);
    }
}
