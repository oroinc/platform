<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\UnitOfWork;

use Symfony\Component\Stopwatch\Stopwatch;

use Oro\Bundle\EntityConfigBundle\Audit\AuditManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigCache;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;

/**
 * Contains tests for a performance crucial parts of entity configs
 * By default all tests are disabled and they are enabled only during
 * modification of entity configs.
 * To enable tests use ENABLE_TESTS constant.
 */
class ConfigManagerPerformanceTest extends \PHPUnit_Framework_TestCase
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

    /** @var ConfigProviderBag */
    protected $configProviderBag;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    public function testGetConfigsForEntities()
    {
        $configManager = $this->createConfigManager();
        // warm-up cache
        $configManager->getConfigs('test');

        self::assertBenchmark(
            __METHOD__,
            7,
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
            2,
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
            1.3,
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
            1.3,
            function () use ($configManager) {
                $configManager->getIds('test', 'Entity1', true);
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
        $this->configProviderBag = new ConfigProviderBag();
        $configProviderBagLink   = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $configProviderBagLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->configProviderBag);

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $emLink   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $emLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->em));

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getRepository')
            ->with(EntityConfigModel::ENTITY_NAME)
            ->willReturn($repo);
        $repo->expects($this->any())
            ->method('findAll')
            ->willReturn($this->getEntityConfigModels());

        $connection    = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $schemaManager = $this->getMockBuilder('Doctrine\DBAL\Schema\AbstractSchemaManager')
            ->disableOriginalConstructor()
            ->setMethods(['tablesExist'])
            ->getMockForAbstractClass();
        $schemaManager->expects($this->any())
            ->method('tablesExist')
            ->willReturn(true);
        $connection->expects($this->any())
            ->method('getSchemaManager')
            ->will($this->returnValue($schemaManager));
        $this->em->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->any())
            ->method('getEntityState')
            ->willReturn(UnitOfWork::STATE_MANAGED);

        $this->metadataFactory = $this->getMockBuilder('Metadata\MetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $securityTokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $securityTokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn(null);

        return new ConfigManager(
            $this->metadataFactory,
            $this->eventDispatcher,
            $configProviderBagLink,
            new ConfigModelManager($emLink),
            new AuditManager($securityTokenStorage),
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
                $model->setMode(ConfigModelManager::MODE_HIDDEN);
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
                $model->setMode(ConfigModelManager::MODE_HIDDEN);
            }

            $models[] = $model;
        }

        return $models;
    }
}
