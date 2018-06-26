<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\UnitOfWork;
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
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Contains tests for a performance crucial parts of entity configs
 * By default all tests are disabled and they are enabled only during
 * modification of ConfigManager and related classes.
 * To enable tests use ENABLE_TESTS constant.
 */
class ConfigManagerPerformanceTest extends \PHPUnit\Framework\TestCase
{
    const ENABLE_TESTS = false;
    const ENABLE_ASSERTS = true;
    const SHOW_DURATIONS = true;

    const NUMBER_OF_ITERATION = 3000;
    const NUMBER_OF_ENTITIES = 150;
    const NUMBER_OF_FIELDS = 30;

    /** @var int */
    protected static $standardDuration;

    /** @var Stopwatch */
    protected static $stopwatch;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $metadataFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

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

    public static function setUpBeforeClass()
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

    public static function tearDownAfterClass()
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

    /**
     * @param int $i
     *
     * @return int
     */
    protected static function doStandardOperation($i)
    {
        return $i;
    }

    /**
     * @param string   $testName
     * @param float    $maxDeviation
     * @param callable $function
     */
    protected static function assertBenchmark($testName, $maxDeviation, $function)
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
     * @param string   $testName
     * @param callable $function
     *
     * @return int The duration (in milliseconds)
     */
    protected static function benchmark($testName, $function)
    {
        if (!self::ENABLE_TESTS) {
            return 0;
        }

        if (false !== $pos = strpos($testName, '::')) {
            $testName = substr($testName, $pos + 2);
        }
        self::$stopwatch->start($testName);
        for ($i = 0; $i < self::NUMBER_OF_ITERATION; $i++) {
            call_user_func($function);
        }
        self::$stopwatch->stop($testName);

        return self::$stopwatch->getEvent($testName)->getDuration();
    }

    /**
     * @return ConfigManager
     */
    protected function createConfigManager()
    {
        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $emLink   = $this->createMock(ServiceLink::class);
        $emLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->em));

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel')
            ->willReturn($repo);
        $repo->expects($this->any())
            ->method('findAll')
            ->willReturn($this->getEntityConfigModels());
        $repo->expects($this->any())
            ->method('findOneBy')
            ->willReturnCallback(
                function ($criteria) {
                    $className = $criteria['className'];
                    foreach ($this->getEntityConfigModels() as $model) {
                        if ($className === $model->getClassName()) {
                            return $model;
                        }
                    }

                    return null;
                }
            );

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->any())
            ->method('getEntityState')
            ->willReturn(UnitOfWork::STATE_MANAGED);

        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataFactory = $this->getMockBuilder('Metadata\MetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $securityTokenStorage = $this
            ->createMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $securityTokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn(null);

        $lockObject = new LockObject();
        $databaseChecker = new ConfigDatabaseChecker($lockObject, $doctrine, [], true);

        return new ConfigManager(
            $this->eventDispatcher,
            $this->metadataFactory,
            new ConfigModelManager($emLink, $lockObject, $databaseChecker),
            new AuditManager($securityTokenStorage, $doctrine),
            new ConfigCache(new ArrayCache(), new ArrayCache())
        );
    }

    /**
     * @return EntityConfigModel[]
     */
    protected function getEntityConfigModels()
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
    protected function getFieldConfigModels()
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
