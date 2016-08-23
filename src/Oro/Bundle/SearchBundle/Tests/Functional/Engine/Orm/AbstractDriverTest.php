<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine\Orm;

use Oro\Bundle\SearchBundle\Engine\Orm\BaseDriver;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\QueryTracker;

use Doctrine\ORM\Configuration;

abstract class AbstractDriverTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        if ($this->getContainer()->getParameter('database_driver') !== static::DRIVER) {
            $this->markTestSkipped(sprintf('Test runs only on %s environment', static::ENVIRONMENT_NAME));
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testOnlyItemIsAccepted()
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager('search');
        $classMetadata = $entityManager->getClassMetadata('Oro\Bundle\SearchBundle\Entity\IndexText');

        $driver = $this->getDriver();
        $driver->initRepo($entityManager, $classMetadata);
    }

    public function testInitRepo()
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager('search');
        $classMetadata = $entityManager->getClassMetadata('Oro\Bundle\SearchBundle\Entity\Item');

        $driver = $this->getDriver();
        $driver->initRepo($entityManager, $classMetadata);

        $configuration = $entityManager->getConfiguration();

        $this->assertInitConfiguration($configuration);
    }

    public function testTruncateIndex()
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager('search');
        $classMetadata = $entityManager->getClassMetadata('Oro\Bundle\SearchBundle\Entity\Item');

        $queryTracker = new QueryTracker($entityManager);

        $queryTracker->start();

        $driver = $this->getDriver();
        $driver->initRepo($entityManager, $classMetadata);
        $driver->truncateIndex();

        $queryTracker->stop();

        $queries = $queryTracker->getExecutedQueries();

        $this->assertTruncateQueries($queries);
    }

    /**
     * @param Configuration $configuration
     */
    abstract protected function assertInitConfiguration(Configuration $configuration);

    /**
     * @param array $queries
     */
    abstract protected function assertTruncateQueries(array $queries);

    /**
     * @return BaseDriver
     */
    abstract protected function getDriver();
}
