<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\EventListener\SearchEntityConfigListener;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Component\Testing\ReflectionUtil;

class SearchEntityConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_CLASS = 'Test\Class';
    private const TEST_FIELD = 'testField';

    /** @var SearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $searchMappingProvider;

    /** @var IndexerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $searchIndexer;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var SearchEntityConfigListener */
    private $listener;

    protected function setUp(): void
    {
        $this->searchMappingProvider = $this->createMock(SearchMappingProvider::class);
        $this->searchIndexer = $this->createMock(IndexerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new SearchEntityConfigListener(
            $this->searchMappingProvider,
            $this->searchIndexer
        );
    }

    private function getClassNames(SearchEntityConfigListener $listener): array
    {
        return ReflectionUtil::getPropertyValue($listener, 'classNames');
    }

    private function getFieldConfig(string $scope, array $values = []): ConfigInterface
    {
        $config = new Config(new FieldConfigId($scope, self::TEST_CLASS, self::TEST_FIELD));
        $config->setValues($values);

        return $config;
    }

    public function testShouldRunReindexWhenSearchableFlagIsChangedAndFieldIsInActiveState()
    {
        $searchConfig = $this->getFieldConfig('search');
        $configs = [
            'search' => $searchConfig,
            'extend' => $this->getFieldConfig('extend', ['state' => ExtendScope::STATE_ACTIVE])
        ];

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($searchConfig))
            ->willReturn(['searchable' => [false, true]]);

        $this->searchMappingProvider->expects(self::once())
            ->method('getEntityConfig')
            ->with('Test\Class')
            ->willReturn(['mapping_config']);

        $this->searchMappingProvider->expects(self::once())
            ->method('clearCache');
        $this->searchIndexer->expects(self::once())
            ->method('reindex')
            ->with([$searchConfig->getId()->getClassName()]);

        $this->listener->preFlush(new PreFlushConfigEvent($configs, $this->configManager));
        $this->listener->postFlush(new PostFlushConfigEvent([], $this->configManager));

        self::assertEmpty($this->getClassNames($this->listener));
    }

    public function testShouldRunReindexOnlyOnceWhenSeveralFieldsRequiredReindex()
    {
        $searchConfig1 = new Config(new FieldConfigId('search', self::TEST_CLASS, 'field1'));
        $extendConfig1 = new Config(new FieldConfigId('extend', self::TEST_CLASS, 'field1'));
        $extendConfig1->setValues(['state' => ExtendScope::STATE_ACTIVE]);
        $configs1 = [
            'search' => $searchConfig1,
            'extend' => $extendConfig1
        ];

        $searchConfig2 = new Config(new FieldConfigId('search', self::TEST_CLASS, 'field2'));
        $extendConfig2 = new Config(new FieldConfigId('extend', self::TEST_CLASS, 'field2'));
        $extendConfig2->setValues(['state' => ExtendScope::STATE_ACTIVE]);
        $configs2 = [
            'search' => $searchConfig2,
            'extend' => $extendConfig2
        ];

        $this->configManager->expects(self::exactly(2))
            ->method('getConfigChangeSet')
            ->willReturn(['searchable' => [false, true]]);

        $this->searchMappingProvider->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->with('Test\Class')
            ->willReturn(['mapping_config']);

        $this->searchMappingProvider->expects(self::once())
            ->method('clearCache');
        $this->searchIndexer->expects(self::once())
            ->method('reindex')
            ->with([self::TEST_CLASS]);

        $this->listener->preFlush(new PreFlushConfigEvent($configs1, $this->configManager));
        $this->listener->preFlush(new PreFlushConfigEvent($configs2, $this->configManager));
        $this->listener->postFlush(new PostFlushConfigEvent([], $this->configManager));
        $this->listener->postFlush(new PostFlushConfigEvent([], $this->configManager));

        self::assertEmpty($this->getClassNames($this->listener));
    }

    public function testShouldRunReindexForEachTypeOfEntityWhenItsFieldsRequiredReindex()
    {
        $searchConfig1 = new Config(new FieldConfigId('search', 'Test\Class1', 'field1'));
        $extendConfig1 = new Config(new FieldConfigId('extend', 'Test\Class1', 'field1'));
        $extendConfig1->setValues(['state' => ExtendScope::STATE_ACTIVE]);
        $configs1 = [
            'search' => $searchConfig1,
            'extend' => $extendConfig1
        ];

        $searchConfig2 = new Config(new FieldConfigId('search', 'Test\Class2', 'field1'));
        $extendConfig2 = new Config(new FieldConfigId('extend', 'Test\Class2', 'field1'));
        $extendConfig2->setValues(['state' => ExtendScope::STATE_ACTIVE]);
        $configs2 = [
            'search' => $searchConfig2,
            'extend' => $extendConfig2
        ];

        $this->configManager->expects(self::exactly(2))
            ->method('getConfigChangeSet')
            ->willReturn(['searchable' => [false, true]]);

        $this->searchMappingProvider->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->withConsecutive(['Test\Class1'], ['Test\Class2'])
            ->willReturnOnConsecutiveCalls(['mapping_config1'], ['mapping_config2']);

        $this->searchMappingProvider->expects(self::once())
            ->method('clearCache');
        $this->searchIndexer->expects(self::once())
            ->method('reindex')
            ->with(['Test\Class1', 'Test\Class2']);

        $this->listener->preFlush(new PreFlushConfigEvent($configs1, $this->configManager));
        $this->listener->preFlush(new PreFlushConfigEvent($configs2, $this->configManager));
        $this->listener->postFlush(new PostFlushConfigEvent([], $this->configManager));
        $this->listener->postFlush(new PostFlushConfigEvent([], $this->configManager));

        self::assertEmpty($this->getClassNames($this->listener));
    }

    public function testShouldNotReindexForEntityNotHavingSearchConfigSingleEntity()
    {
        $searchConfig = $this->getFieldConfig('search');
        $configs = [
            'search' => $searchConfig,
            'extend' => $this->getFieldConfig('extend', ['state' => ExtendScope::STATE_ACTIVE])
        ];

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($searchConfig))
            ->willReturn(['searchable' => [false, true]]);

        $this->searchMappingProvider->expects(self::once())
            ->method('getEntityConfig')
            ->with('Test\Class')
            ->willReturn([]);

        $this->searchMappingProvider->expects(self::never())
            ->method('clearCache');
        $this->searchIndexer->expects(self::never())
            ->method('reindex');

        $this->listener->preFlush(new PreFlushConfigEvent($configs, $this->configManager));
        $this->listener->postFlush(new PostFlushConfigEvent([], $this->configManager));

        self::assertEmpty($this->getClassNames($this->listener));
    }

    public function testShouldNotReindexForEntityNotHavingSearchConfigMultipleEntities()
    {
        $searchConfig1 = new Config(new FieldConfigId('search', 'Test\Class1', 'field1'));
        $extendConfig1 = new Config(new FieldConfigId('extend', 'Test\Class1', 'field1'));
        $extendConfig1->setValues(['state' => ExtendScope::STATE_ACTIVE]);
        $configs1 = [
            'search' => $searchConfig1,
            'extend' => $extendConfig1
        ];

        $searchConfig2 = new Config(new FieldConfigId('search', 'Test\Class2', 'field1'));
        $extendConfig2 = new Config(new FieldConfigId('extend', 'Test\Class2', 'field1'));
        $extendConfig2->setValues(['state' => ExtendScope::STATE_ACTIVE]);
        $configs2 = [
            'search' => $searchConfig2,
            'extend' => $extendConfig2
        ];

        $this->configManager->expects(self::exactly(2))
            ->method('getConfigChangeSet')
            ->willReturn(['searchable' => [false, true]]);

        $this->searchMappingProvider->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->withConsecutive(['Test\Class1'], ['Test\Class2'])
            ->willReturnOnConsecutiveCalls(['mapping_config1'], []);

        $this->searchMappingProvider->expects(self::once())
            ->method('clearCache');
        $this->searchIndexer->expects(self::once())
            ->method('reindex')
            ->with(['Test\Class1']);

        $this->listener->preFlush(new PreFlushConfigEvent($configs1, $this->configManager));
        $this->listener->preFlush(new PreFlushConfigEvent($configs2, $this->configManager));
        $this->listener->postFlush(new PostFlushConfigEvent([], $this->configManager));
        $this->listener->postFlush(new PostFlushConfigEvent([], $this->configManager));

        self::assertEmpty($this->getClassNames($this->listener));
    }

    public function testShouldIgnoreFieldWhenSearchableFlagIsChangedButFieldIsNotInActiveState()
    {
        $searchConfig = $this->getFieldConfig('search');
        $configs = [
            'search' => $searchConfig,
            'extend' => $this->getFieldConfig('extend', ['state' => ExtendScope::STATE_NEW])
        ];

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($searchConfig))
            ->willReturn(['searchable' => [false, true]]);

        $this->searchMappingProvider->expects(self::never())
            ->method('clearCache');
        $this->searchIndexer->expects(self::never())
            ->method('reindex');

        $this->listener->preFlush(new PreFlushConfigEvent($configs, $this->configManager));
        $this->listener->postFlush(new PostFlushConfigEvent([], $this->configManager));

        self::assertEmpty($this->getClassNames($this->listener));
    }

    public function testShouldIgnoreFieldDoesNotHaveSearchConfig()
    {
        $configs = [
            'extend' => $this->getFieldConfig('extend', ['state' => ExtendScope::STATE_ACTIVE])
        ];

        $this->configManager->expects(self::never())
            ->method('getConfigChangeSet');
        $this->searchMappingProvider->expects(self::never())
            ->method('clearCache');
        $this->searchIndexer->expects(self::never())
            ->method('reindex');

        $this->listener->preFlush(new PreFlushConfigEvent($configs, $this->configManager));
        $this->listener->postFlush(new PostFlushConfigEvent([], $this->configManager));
    }

    public function testShouldIgnoreFieldWhenSearchableFlagIsNotChanged()
    {
        $searchConfig = $this->getFieldConfig('search');
        $configs = [
            'search' => $searchConfig,
            'extend' => $this->getFieldConfig('extend', ['state' => ExtendScope::STATE_ACTIVE])
        ];

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($searchConfig))
            ->willReturn([]);

        $this->searchMappingProvider->expects(self::never())
            ->method('clearCache');
        $this->searchIndexer->expects(self::never())
            ->method('reindex');

        $this->listener->preFlush(new PreFlushConfigEvent($configs, $this->configManager));
        $this->listener->postFlush(new PostFlushConfigEvent([], $this->configManager));
    }

    public function testShouldIgnoreFieldWhenSearchableFlagIsChangedButFieldDoesNotHaveExtendConfig()
    {
        $searchConfig = $this->getFieldConfig('search');
        $configs = [
            'search' => $searchConfig
        ];

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($searchConfig))
            ->willReturn(['searchable' => [false, true]]);

        $this->searchMappingProvider->expects(self::never())
            ->method('clearCache');
        $this->searchIndexer->expects(self::never())
            ->method('reindex');

        $this->listener->preFlush(new PreFlushConfigEvent($configs, $this->configManager));
        $this->listener->postFlush(new PostFlushConfigEvent([], $this->configManager));
    }
}
