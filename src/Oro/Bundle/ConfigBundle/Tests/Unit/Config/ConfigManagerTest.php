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
    private array $settings = [
        'oro_user' => [
            'item1' => [
                'value' => true,
                'type' => 'boolean',
            ],
            'item2' => [
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

    /** @var ConfigManager */
    private $manager;

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
    public function testSet(object|int|null $scopeIdentifier): void
    {
        $itemName = 'test.item';
        $itemValue = 111;

        $this->userScopeManager->expects(self::once())
            ->method('set')
            ->with($itemName, $itemValue, $scopeIdentifier);

        $this->memoryCache->expects(self::once())
            ->method('deleteAll');

        $this->manager->set($itemName, $itemValue, $scopeIdentifier);
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testReset(object|int|null $scopeIdentifier): void
    {
        $itemName = 'test.item';

        $this->userScopeManager->expects(self::once())
            ->method('reset')
            ->with($itemName, $scopeIdentifier);

        $this->memoryCache->expects(self::once())
            ->method('deleteAll');

        $this->manager->reset($itemName, $scopeIdentifier);
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testFlush(object|int|null $scopeIdentifier, int $idValue): void
    {
        $item1Name = 'oro_user.item1';
        $item1Value = [
            'value' => 'updated value',
            'use_parent_scope_value' => false
        ];
        $changes = [
            $item1Name => $item1Value
        ];

        $expectedScopeIdentifier = $scopeIdentifier;
        if (null === $scopeIdentifier) {
            $expectedScopeIdentifier = $idValue;
            $this->userScopeManager->expects(self::once())
                ->method('getChangedScopeIdentifiers')
                ->willReturn([$idValue]);
        }

        $this->userScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($item1Name)
            ->willReturn(['value' => 'old value']);

        $singleKeyBeforeEvent = new ConfigSettingsUpdateEvent($this->manager, $item1Value);
        $beforeEvent = new ConfigSettingsUpdateEvent($this->manager, $changes);
        $afterEvent  = new ConfigUpdateEvent(
            [$item1Name => ['old' => 'old value', 'new' => 'updated value']],
            'user',
            $idValue
        );

        $loadEvent = new ConfigGetEvent($this->manager, $item1Name, 'old value', false, 'user', $idValue);

        $this->userScopeManager->expects(self::once())
            ->method('getChanges')
            ->willReturn($changes);
        $this->userScopeManager->expects(self::any())
            ->method('resolveIdentifier')
            ->willReturn($idValue);
        $this->userScopeManager->expects(self::any())
            ->method('getScopeIdFromEntity')
            ->willReturn($idValue);
        $this->userScopeManager->expects(self::any())
            ->method('getScopeId')
            ->willReturn($idValue);

        $this->userScopeManager->expects(self::once())
            ->method('save')
            ->with($changes, $expectedScopeIdentifier)
            ->willReturn([
                [$item1Name => 'updated value'],
                []
            ]);

        $this->dispatcher->expects(self::exactly(5))
            ->method('dispatch')
            ->withConsecutive(
                [$loadEvent, ConfigGetEvent::NAME],
                [$loadEvent, ConfigGetEvent::NAME . '.' . $item1Name],
                [$singleKeyBeforeEvent, ConfigSettingsUpdateEvent::BEFORE_SAVE . '.' . $item1Name],
                [$beforeEvent, ConfigSettingsUpdateEvent::BEFORE_SAVE],
                [$afterEvent, ConfigUpdateEvent::EVENT_NAME]
            );

        $this->manager->flush($scopeIdentifier);
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testSave(object|int|null $scopeIdentifier, int $idValue): void
    {
        $item1Name = 'oro_user.item1';
        $item2Name = 'oro_user.item2';

        $data = [
            'oro_user___item1'   => [
                'value'                  => 'updated value',
                'use_parent_scope_value' => false
            ],
            'oro_user___unknown' => [
                'value'                  => 'some value',
                'use_parent_scope_value' => false
            ],
            'oro_user___item2'   => [
                'use_parent_scope_value' => true
            ]
        ];

        $item1sValue = [
            'value'                  => 'updated value',
            'use_parent_scope_value' => false
        ];
        $item2Value = [
            'use_parent_scope_value' => true
        ];
        $normalizedData = [
            $item1Name => $item1sValue,
            $item2Name => $item2Value,
        ];

        $this->userScopeManager->expects(self::any())
            ->method('resolveIdentifier')
            ->willReturn($idValue);
        $this->userScopeManager->expects(self::any())
            ->method('getScopeIdFromEntity')
            ->willReturn($idValue);
        $this->userScopeManager->expects(self::any())
            ->method('getScopeId')
            ->willReturn($idValue);

        $this->userScopeManager->expects(self::exactly(2))
            ->method('getSettingValue')
            ->willReturnMap([
                [$item1Name, true, $scopeIdentifier, true, ['value' => 'old value']],
                [$item2Name, true, $scopeIdentifier, true, ['value' => 2000]]
            ]);

        $item1BeforeEvent = new ConfigSettingsUpdateEvent($this->manager, $item1sValue);
        $item2BeforeEvent = new ConfigSettingsUpdateEvent($this->manager, $item2Value);
        $beforeEvent = new ConfigSettingsUpdateEvent($this->manager, $normalizedData);
        $afterEvent = new ConfigUpdateEvent(
            [
                $item1Name => ['old' => 'old value', 'new' => 'updated value'],
                $item2Name => ['old' => 2000, 'new' => 20]
            ],
            'user',
            $idValue
        );

        $item1OldValueLoadEvent = new ConfigGetEvent($this->manager, $item1Name, 'old value', false, 'user', $idValue);
        $item2OldValueLoadEvent = new ConfigGetEvent($this->manager, $item2Name, '2000', false, 'user', $idValue);
        $item2NullValueLoadEvent = new ConfigGetEvent($this->manager, $item2Name, null, false, 'user', $idValue);

        $this->userScopeManager->expects(self::once())
            ->method('save')
            ->with($normalizedData)
            ->willReturn([
                [$item1Name => 'updated value'],
                [$item2Name]
            ]);

        $this->dispatcher->expects(self::exactly(10))
            ->method('dispatch')
            ->withConsecutive(
                [$item1OldValueLoadEvent, ConfigGetEvent::NAME],
                [$item1OldValueLoadEvent, ConfigGetEvent::NAME . '.' . $item1Name],
                [$item1BeforeEvent, ConfigSettingsUpdateEvent::BEFORE_SAVE . '.' . $item1Name],
                [$item2OldValueLoadEvent, ConfigGetEvent::NAME],
                [$item2OldValueLoadEvent, ConfigGetEvent::NAME . '.' . $item2Name],
                [$item2BeforeEvent, ConfigSettingsUpdateEvent::BEFORE_SAVE . '.' . $item2Name],
                [$beforeEvent, ConfigSettingsUpdateEvent::BEFORE_SAVE],
                [$item2NullValueLoadEvent, ConfigGetEvent::NAME],
                [$item2NullValueLoadEvent, ConfigGetEvent::NAME  . '.' . $item2Name],
                [$afterEvent, ConfigUpdateEvent::EVENT_NAME]
            );

        $this->memoryCache->expects(self::once())
            ->method('deleteAll');

        $this->manager->save($data, $scopeIdentifier);
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testCalculateChangeSet(object|int|null $scopeIdentifier): void
    {
        $data = [];

        $this->userScopeManager->expects(self::once())
            ->method('calculateChangeSet')
            ->with($data, $scopeIdentifier);

        $this->manager->calculateChangeSet($data, $scopeIdentifier);
    }

    public function testReload(): void
    {
        $this->userScopeManager->expects(self::once())
            ->method('reload');

        $this->memoryCache->expects(self::once())
            ->method('deleteAll');

        $this->manager->reload();
    }

    /**
     * @dataProvider getFromParentParamProvider
     */
    public function testGetFromParentScope(string $itemName, bool $full, mixed $expectedResult): void
    {
        $this->userScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName)
            ->willReturn(null);

        $this->globalScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName)
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

        self::assertEquals($expectedResult, $this->manager->get($itemName, false, $full));
    }

    public function getFromParentParamProvider(): array
    {
        return [
            [
                'itemName'       => 'oro_test.someValue',
                'full'           => false,
                'expectedResult' => 1,
            ],
            [
                'itemName'       => 'oro_test.someValue',
                'full'           => true,
                'expectedResult' => ['scope' => 'global', 'value' => 1, 'use_parent_scope_value' => true],
            ],
            [
                'itemName'       => 'oro_test.someArrayValue',
                'full'           => true,
                'expectedResult' => [
                    'scope'                  => 'global',
                    'value'                  => ['foo' => 'bar'],
                    'use_parent_scope_value' => true
                ]
            ],
            [
                'itemName'       => 'oro_test.someArrayValue',
                'full'           => false,
                'expectedResult' => ['foo' => 'bar']
            ]
        ];
    }

    public function testGetWithoutScopeIdentifier(): void
    {
        $itemName = 'oro_test.someValue';
        $itemValue = 111;
        $resolvedScopeId = 10;

        $this->userScopeManager->expects(self::exactly(2))
            ->method('getScopedEntityName')
            ->willReturn('user');
        $this->userScopeManager->expects(self::exactly(2))
            ->method('resolveIdentifier')
            ->with(self::isNull())
            ->willReturn($resolvedScopeId);
        $this->userScopeManager->expects(self::once())
            ->method('getScopeId')
            ->willReturn($resolvedScopeId);

        $this->userScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, self::isTrue(), self::isNull())
            ->willReturn(['value' => $itemValue]);

        $loadEvent = new ConfigGetEvent($this->manager, $itemName, $itemValue, false, 'user', $resolvedScopeId);
        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$loadEvent, ConfigGetEvent::NAME],
                [$loadEvent, ConfigGetEvent::NAME . '.' . $itemName]
            );

        $this->globalScopeManager->expects(self::never())
            ->method('getSettingValue');

        self::assertSame($itemValue, $this->manager->get($itemName));
        // check cache
        self::assertSame($itemValue, $this->manager->get($itemName));
    }

    public function testGetWithIntScopeIdentifier(): void
    {
        $itemName = 'oro_test.someValue';
        $itemValue = 111;
        $scopeIdentifier = 10;

        $this->userScopeManager->expects(self::exactly(2))
            ->method('getScopedEntityName')
            ->willReturn('user');
        $this->userScopeManager->expects(self::never())
            ->method('resolveIdentifier');
        $this->userScopeManager->expects(self::never())
            ->method('getScopeId');

        $this->userScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, self::isTrue(), $scopeIdentifier)
            ->willReturn(['value' => $itemValue]);

        $loadEvent = new ConfigGetEvent($this->manager, $itemName, $itemValue, false, 'user', $scopeIdentifier);
        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$loadEvent, ConfigGetEvent::NAME],
                [$loadEvent, ConfigGetEvent::NAME . '.' . $itemName]
            );

        $this->globalScopeManager->expects(self::never())
            ->method('getSettingValue');

        self::assertSame($itemValue, $this->manager->get($itemName, false, false, $scopeIdentifier));
        // check cache
        self::assertSame($itemValue, $this->manager->get($itemName, false, false, $scopeIdentifier));
    }

    public function testGetWithZeroIntScopeIdentifier(): void
    {
        $itemName = 'oro_test.someValue';
        $itemValue = 111;
        $scopeIdentifier = 0;

        $this->userScopeManager->expects(self::exactly(2))
            ->method('getScopedEntityName')
            ->willReturn('user');
        $this->userScopeManager->expects(self::never())
            ->method('resolveIdentifier');
        $this->userScopeManager->expects(self::never())
            ->method('getScopeId');

        $this->userScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, self::isTrue(), $scopeIdentifier)
            ->willReturn(['value' => $itemValue]);

        $loadEvent = new ConfigGetEvent($this->manager, $itemName, $itemValue, false, 'user', $scopeIdentifier);
        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$loadEvent, ConfigGetEvent::NAME],
                [$loadEvent, ConfigGetEvent::NAME . '.' . $itemName]
            );

        $this->globalScopeManager->expects(self::never())
            ->method('getSettingValue');

        self::assertSame($itemValue, $this->manager->get($itemName, false, false, $scopeIdentifier));
        // check cache
        self::assertSame($itemValue, $this->manager->get($itemName, false, false, $scopeIdentifier));
    }

    public function testGetWithObjectScopeIdentifier(): void
    {
        $itemName = 'oro_test.someValue';
        $itemValue = 111;
        $scopeIdentifier = new \stdClass();
        $resolvedScopeId = 10;

        $this->userScopeManager->expects(self::exactly(2))
            ->method('getScopedEntityName')
            ->willReturn('user');
        $this->userScopeManager->expects(self::exactly(3))
            ->method('getScopeIdFromEntity')
            ->willReturn($resolvedScopeId);
        $this->userScopeManager->expects(self::never())
            ->method('getScopeId');

        $this->userScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, self::isTrue(), $scopeIdentifier)
            ->willReturn(['value' => $itemValue]);

        $loadEvent = new ConfigGetEvent($this->manager, $itemName, $itemValue, false, 'user', $resolvedScopeId);
        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$loadEvent, ConfigGetEvent::NAME],
                [$loadEvent, ConfigGetEvent::NAME . '.' . $itemName]
            );

        $this->globalScopeManager->expects(self::never())
            ->method('getSettingValue');

        self::assertSame($itemValue, $this->manager->get($itemName, false, false, $scopeIdentifier));
        // check cache
        self::assertSame($itemValue, $this->manager->get($itemName, false, false, $scopeIdentifier));
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testGetDefaultSettings(object|int|null $scopeIdentifier): void
    {
        $itemName = 'oro_test.anysetting';

        $this->userScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, true, $scopeIdentifier)
            ->willReturn(null);

        $this->globalScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, true, $scopeIdentifier)
            ->willReturn(null);

        self::assertEquals('anyvalue', $this->manager->get($itemName, false, false, $scopeIdentifier));
        // check cache
        self::assertEquals('anyvalue', $this->manager->get($itemName, false, false, $scopeIdentifier));
    }

    public function testGetEmptyValueSettings(): void
    {
        $itemName = 'oro_test.emptystring';

        $this->userScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, true)
            ->willReturn(['value' => '']);

        $this->globalScopeManager->expects(self::never())
            ->method('getSettingValue');

        self::assertSame('', $this->manager->get($itemName));
        // check cache
        self::assertSame('', $this->manager->get($itemName));
    }

    public function testGetSettingsDefaultsMethodReturnNullForMissing(): void
    {
        self::assertNull($this->manager->getSettingsDefaults('oro_test.null', true));
    }

    public function testGetSettingsDefaultsMethodReturnPlain(): void
    {
        self::assertEquals('anyvalue', $this->manager->getSettingsDefaults('oro_test.anysetting'));
    }

    public function testGetSettingsDefaultsMethodReturnFull(): void
    {
        self::assertEquals(
            ['value' => 'anyvalue', 'type' => 'scalar'],
            $this->manager->getSettingsDefaults('oro_test.anysetting', true)
        );
    }

    public function testGetMergedWithParentValueForNotArrayValue(): void
    {
        $value = 'val';

        $this->userScopeManager->expects(self::never())
            ->method('getSettingValue');

        $this->globalScopeManager->expects(self::never())
            ->method('getSettingValue');

        self::assertEquals(
            $value,
            $this->manager->getMergedWithParentValue($value, 'test.item')
        );
    }

    public function testGetMergedWithParentValueForNotArrayValueWithFull(): void
    {
        $value = [ConfigManager::SCOPE_KEY => 'test', ConfigManager::VALUE_KEY => 'val'];

        $this->userScopeManager->expects(self::never())
            ->method('getSettingValue');

        $this->globalScopeManager->expects(self::never())
            ->method('getSettingValue');

        self::assertEquals(
            $value,
            $this->manager->getMergedWithParentValue($value, 'test.item', true)
        );
    }

    /**
     * @dataProvider getMergedWithParentValueDataProvider
     */
    public function testGetMergedWithParentValue(
        array $value,
        mixed $userValue,
        mixed $globalValue,
        array $resultValue
    ): void {
        $itemName = 'test.item';

        $this->userScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, false)
            ->willReturn($userValue);

        $this->globalScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, false)
            ->willReturn($globalValue);

        self::assertEquals(
            $resultValue,
            $this->manager->getMergedWithParentValue($value, $itemName)
        );
    }

    /**
     * @dataProvider getMergedWithParentValueDataProvider
     */
    public function testGetMergedWithParentValueWithFull(
        array $value,
        mixed $userValue,
        mixed $globalValue,
        array $resultValue
    ): void {
        $itemName = 'test.item';

        $this->userScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, true)
            ->willReturn([ConfigManager::VALUE_KEY => $userValue]);

        $this->globalScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, true)
            ->willReturn([ConfigManager::VALUE_KEY => $globalValue]);

        $value = [ConfigManager::SCOPE_KEY => 'test', ConfigManager::VALUE_KEY => $value];
        $resultValue = [ConfigManager::SCOPE_KEY => 'test', ConfigManager::VALUE_KEY => $resultValue];
        self::assertEquals(
            $resultValue,
            $this->manager->getMergedWithParentValue($value, $itemName, true)
        );
    }

    public static function getMergedWithParentValueDataProvider(): array
    {
        return [
            [
                'value'       => [],
                'userValue'   => ['a' => null, 'b' => 1, 'c' => 1],
                'globalValue' => ['a' => 1, 'b' => 2, 'd' => 1],
                'resultValue' => ['a' => null, 'b' => 1, 'c' => 1, 'd' => 1]
            ],
            [
                'value'       => ['b' => 0],
                'userValue'   => ['a' => null, 'b' => 1, 'c' => 1],
                'globalValue' => ['a' => 1, 'b' => 2, 'd' => 1],
                'resultValue' => ['a' => null, 'b' => 0, 'c' => 1, 'd' => 1]
            ],
            [
                'value'       => ['b' => 0],
                'userValue'   => null,
                'globalValue' => ['a' => 1, 'b' => 2, 'd' => 1],
                'resultValue' => ['a' => 1, 'b' => 0, 'd' => 1]
            ],
            [
                'value'       => ['b' => 0],
                'userValue'   => null,
                'globalValue' => null,
                'resultValue' => ['b' => 0]
            ],
            [
                'value'       => [],
                'userValue'   => 'user val',
                'globalValue' => 'global val',
                'resultValue' => ['global val', 'user val']
            ],
            [
                'value'       => ['test val'],
                'userValue'   => 'user val',
                'globalValue' => 'global val',
                'resultValue' => ['global val', 'user val', 'test val']
            ],
            [
                'value'       => ['test val'],
                'userValue'   => null,
                'globalValue' => 'global val',
                'resultValue' => ['global val', 'test val']
            ],
            [
                'value'       => ['test val'],
                'userValue'   => null,
                'globalValue' => null,
                'resultValue' => ['test val']
            ],
            [
                'value'       => ['test val'],
                'userValue'   => '',
                'globalValue' => 'global val',
                'resultValue' => ['global val', '', 'test val']
            ]
        ];
    }

    public function testGetValues(): void
    {
        $itemName = 'oro_test.someValue';
        $entity1 = new \stdClass();
        $entity1->id = 1;
        $entity2 = new \stdClass();
        $entity2->id = 2;
        $entities = [$entity1, $entity2];

        $this->userScopeManager->expects(self::any())
            ->method('resolveIdentifier')
            ->willReturnMap([
                [$entity1, null],
                [$entity2, 55]
            ]);
        $this->globalScopeManager->expects(self::any())
            ->method('resolveIdentifier')
            ->willReturnMap([
                [$entity1, 33],
            ]);

        $this->userScopeManager->expects(self::any())
            ->method('getScopeIdFromEntity')
            ->willReturnCallback(function (\stdClass $scope) {
                return $scope->id;
            });
        $this->globalScopeManager->expects(self::never())
            ->method('getScopeIdFromEntity');

        $this->userScopeManager->expects(self::exactly(2))
            ->method('getSettingValue')
            ->willReturnMap([
                [$itemName, true, $entity1, false, ['value' => 'val1']],
                [$itemName, true, $entity2, false, ['value' => 'val2']]
            ]);
        $this->globalScopeManager->expects(self::never())
            ->method('getSettingValue');

        self::assertEquals([33 => 'val1', 55 => 'val2'], $this->manager->getValues($itemName, $entities));
        // check cache
        self::assertEquals([33 => 'val1', 55 => 'val2'], $this->manager->getValues($itemName, $entities));
    }

    public static function scopeIdentifierDataProvider(): array
    {
        return [
            [null, 0],
            [2, 2],
            [new \stdClass(), 123]
        ];
    }

    /**
     * @dataProvider scopeIdentifierDataProvider
     */
    public function testGetDefaultSettingsFromProvider(object|int|null $scopeIdentifier): void
    {
        $itemName = 'oro_test.servicestring';
        $itemValue = 111;

        $this->userScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, true, $scopeIdentifier)
            ->willReturn(null);

        $this->globalScopeManager->expects(self::once())
            ->method('getSettingValue')
            ->with($itemName, true, $scopeIdentifier)
            ->willReturn(null);

        $this->defaultValueProvider->expects(self::once())
            ->method('getValue')
            ->willReturn($itemValue);

        self::assertSame($itemValue, $this->manager->get($itemName, false, false, $scopeIdentifier));
        // check cache
        self::assertSame($itemValue, $this->manager->get($itemName, false, false, $scopeIdentifier));
    }

    public function testDeleteScope(): void
    {
        $scopeIdentifier = 1;

        $this->userScopeManager->expects(self::once())
            ->method('deleteScope')
            ->with($scopeIdentifier);

        $this->manager->deleteScope($scopeIdentifier);
    }

    public function testDeleteScopeWithObjectAsIdentifier(): void
    {
        $scopeIdentifier = new \stdClass();

        $this->userScopeManager->expects(self::once())
            ->method('deleteScope')
            ->with($scopeIdentifier);

        $this->manager->deleteScope($scopeIdentifier);
    }
}
