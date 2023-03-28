<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityConfigBundle\Audit\AuditManager;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigCache;
use Oro\Bundle\EntityConfigBundle\Config\ConfigDatabaseChecker;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Config\LockObject;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Metadata\Factory\MetadataFactory;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock\ConfigurationHandlerMock;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Contains tests for a performance crucial parts of entity configs
 * By default all tests are disabled and they are enabled only during
 * modification of ConfigManager and related classes.
 * To enable tests use ENABLE_TESTS constant.
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigManagerPerformanceTest extends \PHPUnit\Framework\TestCase
{
    private const ENABLE_TESTS = false;
    private const ENABLE_ASSERTS = true;
    private const SHOW_DURATIONS = true;

    private const NUMBER_OF_ITERATION = 3000;
    private const NUMBER_OF_ENTITIES = 150;
    private const NUMBER_OF_FIELDS = 30;

    /** @var int */
    private static $standardDuration;

    /** @var Stopwatch */
    private static $stopwatch;

    public function testConfigGet()
    {
        $config = new Config(new EntityConfigId('test', 'Entity1'), ['attr' => 'val', 'null_attr' => null]);

        self::assertBenchmark(
            __METHOD__,
            0.05,
            function () use ($config) {
                $config->get('attr');
                $config->get('null_attr');
                $config->get('undefined_attr');
            }
        );
    }

    public function testConfigHas()
    {
        $config = new Config(new EntityConfigId('test', 'Entity1'), ['attr' => 'val', 'null_attr' => null]);

        self::assertBenchmark(
            __METHOD__,
            0.05,
            function () use ($config) {
                $config->has('attr');
                $config->has('null_attr');
                $config->has('undefined_attr');
            }
        );
    }

    public function testGetConfigsForEntities()
    {
        $configManager = $this->createConfigManager();
        // warm-up cache
        $configManager->getConfigs('test');

        self::assertBenchmark(
            __METHOD__,
            6.5,
            function () use ($configManager) {
                $configManager->getConfigs('test');
            }
        );
    }

    public function testGetConfigsForEntitiesWithHidden()
    {
        $configManager = $this->createConfigManager();
        // warm-up cache
        $configManager->getConfigs('test', null, true);

        self::assertBenchmark(
            __METHOD__,
            7,
            function () use ($configManager) {
                $configManager->getConfigs('test', null, true);
            }
        );
    }

    public function testGetConfigsForFields()
    {
        $configManager = $this->createConfigManager();
        // warm-up cache
        $configManager->getConfigs('test', 'Entity1');

        self::assertBenchmark(
            __METHOD__,
            1.7,
            function () use ($configManager) {
                $configManager->getConfigs('test', 'Entity1');
            }
        );
    }

    public function testGetConfigsForFieldsWithHidden()
    {
        $configManager = $this->createConfigManager();
        // warm-up cache
        $configManager->getConfigs('test', 'Entity1', true);

        self::assertBenchmark(
            __METHOD__,
            2,
            function () use ($configManager) {
                $configManager->getConfigs('test', 'Entity1', true);
            }
        );
    }

    public function testGetIdsForEntities()
    {
        $configManager = $this->createConfigManager();
        // warm-up cache
        $configManager->getIds('test');

        self::assertBenchmark(
            __METHOD__,
            4,
            function () use ($configManager) {
                $configManager->getIds('test');
            }
        );
    }

    public function testGetIdsForEntitiesWithHidden()
    {
        $configManager = $this->createConfigManager();
        // warm-up cache
        $configManager->getIds('test', null, true);

        self::assertBenchmark(
            __METHOD__,
            4,
            function () use ($configManager) {
                $configManager->getIds('test', null, true);
            }
        );
    }

    public function testGetIdsForFields()
    {
        $configManager = $this->createConfigManager();
        // warm-up cache
        $configManager->getIds('test', 'Entity1');

        self::assertBenchmark(
            __METHOD__,
            1.1,
            function () use ($configManager) {
                $configManager->getIds('test', 'Entity1');
            }
        );
    }

    public function testGetIdsForFieldsWithHidden()
    {
        $configManager = $this->createConfigManager();
        // warm-up cache
        $configManager->getIds('test', 'Entity1', true);

        self::assertBenchmark(
            __METHOD__,
            1.1,
            function () use ($configManager) {
                $configManager->getIds('test', 'Entity1', true);
            }
        );
    }

    public function testHasConfigForEntity()
    {
        $configManager = $this->createConfigManager();
        $className = 'Entity1';
        // warm-up cache
        $this->assertTrue($configManager->hasConfig($className));

        self::assertBenchmark(
            __METHOD__,
            0.1,
            function () use ($configManager, $className) {
                $configManager->hasConfig($className);
            }
        );
    }

    public function testHasConfigForField()
    {
        $configManager = $this->createConfigManager();
        $className = 'Entity1';
        $fieldName = 'field1';
        // warm-up cache
        $this->assertTrue($configManager->hasConfig($className, $fieldName));

        self::assertBenchmark(
            __METHOD__,
            0.1,
            function () use ($configManager, $className, $fieldName) {
                $configManager->hasConfig($className, $fieldName);
            }
        );
    }

    public function testGetConfigForEntity()
    {
        $configManager = $this->createConfigManager();
        $configId = new EntityConfigId('test', 'Entity1');
        // warm-up cache
        $this->assertNotNull($configManager->getConfig($configId));

        self::assertBenchmark(
            __METHOD__,
            0.1,
            function () use ($configManager, $configId) {
                $configManager->getConfig($configId);
            }
        );
    }

    public function testGetConfigForField()
    {
        $configManager = $this->createConfigManager();
        $configId = new FieldConfigId('test', 'Entity1', 'field1');
        // warm-up cache
        $this->assertNotNull($configManager->getConfig($configId));

        self::assertBenchmark(
            __METHOD__,
            0.1,
            function () use ($configManager, $configId) {
                $configManager->getConfig($configId);
            }
        );
    }

    public function testGetIdForEntity()
    {
        $configManager = $this->createConfigManager();
        // warm-up cache
        $this->assertNotNull($configManager->getId('test', 'Entity1'));

        self::assertBenchmark(
            __METHOD__,
            0.1,
            function () use ($configManager) {
                $configManager->getId('test', 'Entity1');
            }
        );
    }

    public function testGetIdForField()
    {
        $configManager = $this->createConfigManager();
        // warm-up cache
        $this->assertNotNull($configManager->getId('test', 'Entity1', 'field1'));

        self::assertBenchmark(
            __METHOD__,
            0.1,
            function () use ($configManager) {
                $configManager->getId('test', 'Entity1', 'field1');
            }
        );
    }

    public static function setUpBeforeClass(): void
    {
        self::$stopwatch = new Stopwatch();

        self::$standardDuration = self::benchmark(
            'STANDARD',
            function () {
                $var = 0;
                for ($i = 1; $i <= self::NUMBER_OF_ENTITIES; $i++) {
                    $var += self::doStandardOperation($i);
                }
            }
        );
    }

    public static function tearDownAfterClass(): void
    {
        if (self::ENABLE_TESTS && self::SHOW_DURATIONS) {
            $result = PHP_EOL;
            foreach (self::$stopwatch->getSections() as $section) {
                foreach ($section->getEvents() as $testName => $event) {
                    $result .=
                        sprintf(
                            'Test: %s. Duration: %d ms. Deviation: %.2f.',
                            $testName,
                            $event->getDuration(),
                            $event->getDuration() / (self::$standardDuration ?: 1)
                        )
                        . PHP_EOL;
                }
            }
            echo $result;
        }
        self::$stopwatch = null;
    }

    private static function doStandardOperation(int $i): int
    {
        return $i;
    }

    private static function assertBenchmark(string $testName, float $maxDeviation, callable $function): void
    {
        $duration = self::benchmark($testName, $function);

        $maxDuration = (int)(self::$standardDuration * $maxDeviation);
        if ($duration > $maxDuration && self::ENABLE_ASSERTS) {
            self::fail(
                sprintf(
                    'Failed asserting that the test duration is less than %d ms. Actual duration is %d ms.',
                    $maxDuration,
                    $duration
                )
            );
        }
    }

    /**
     * Measures how many long the given test works.
     * The returned value is in milliseconds.
     */
    private static function benchmark(string $testName, callable $function): int
    {
        if (!self::ENABLE_TESTS) {
            return 0;
        }

        $pos = strpos($testName, '::');
        if (false !== $pos) {
            $testName = substr($testName, $pos + 2);
        }
        self::$stopwatch->start($testName);
        for ($i = 0; $i < self::NUMBER_OF_ITERATION; $i++) {
            $function();
        }
        self::$stopwatch->stop($testName);

        return self::$stopwatch->getEvent($testName)->getDuration();
    }

    private function createConfigManager(): ConfigManager
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(EntityConfigModel::class)
            ->willReturn($repo);
        $repo->expects($this->any())
            ->method('findAll')
            ->willReturn($this->getEntityConfigModels());
        $repo->expects($this->any())
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) {
                $className = $criteria['className'];
                foreach ($this->getEntityConfigModels() as $model) {
                    if ($className === $model->getClassName()) {
                        return $model;
                    }
                }

                return null;
            });

        $uow = $this->createMock(UnitOfWork::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->any())
            ->method('getEntityState')
            ->willReturn(UnitOfWork::STATE_MANAGED);

        $securityTokenStorage = $this->createMock(TokenStorageInterface::class);
        $securityTokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn(null);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(EntityConfigModel::class)
            ->willReturn($em);

        $lockObject = new LockObject();
        $applicationState = $this->createMock(ApplicationState::class);

        $applicationState->method('isInstalled')->willReturn(true);

        $databaseChecker = new ConfigDatabaseChecker($lockObject, $doctrine, [], $applicationState);

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $modelCache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cache->expects($this->any())
            ->method('getItem')
            ->willReturn($cacheItem);
        $modelCache->expects($this->any())
            ->method('getItem')
            ->willReturn($cacheItem);
        $serviceProvider = new ServiceLocator([
            'annotation_metadata_factory' => function () {
                return $this->createMock(MetadataFactory::class);
            },
            'configuration_handler' => function () {
                return ConfigurationHandlerMock::getInstance();
            },
            'event_dispatcher' => function () {
                return $this->createMock(EventDispatcher::class);
            },
            'audit_manager' => function () use ($securityTokenStorage, $doctrine) {
                return new AuditManager($securityTokenStorage, $doctrine);
            },
            'config_model_manager' => function () use ($doctrine, $lockObject, $databaseChecker) {
                return new ConfigModelManager($doctrine, $lockObject, $databaseChecker);
            }
        ]);

        return new ConfigManager(
            new ConfigCache($cache, $modelCache, ['test' => 'test']),
            $serviceProvider
        );
    }

    /**
     * @return EntityConfigModel[]
     */
    private function getEntityConfigModels(): array
    {
        $models = [];
        for ($i = 1; $i <= self::NUMBER_OF_ENTITIES; $i++) {
            $model = new EntityConfigModel('Entity' . $i);
            if ($i % 20 === 0) {
                $model->setMode(ConfigModel::MODE_HIDDEN);
            }

            foreach ($this->getFieldConfigModels() as $field) {
                $model->addField($field);
            }

            $models[] = $model;
        }

        return $models;
    }

    /**
     * @return FieldConfigModel[]
     */
    private function getFieldConfigModels(): array
    {
        $models = [];
        for ($i = 1; $i <= self::NUMBER_OF_FIELDS; $i++) {
            $model = new FieldConfigModel('field' . $i, 'string');
            if ($i % 5 === 0) {
                $model->setMode(ConfigModel::MODE_HIDDEN);
            }

            $models[] = $model;
        }

        return $models;
    }
}
