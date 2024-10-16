<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine\Orm;

use Doctrine\ORM\Configuration;
use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;
use Oro\Bundle\SearchBundle\Engine\Orm\BaseDriver;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractDriverTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testOnlyItemIsAccepted()
    {
        $this->expectException(\InvalidArgumentException::class);

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

        $queryAnalyzer = new QueryAnalyzer($entityManager->getConnection()->getDatabasePlatform());

        $previousLogger = $entityManager->getConnection()->getConfiguration()->getSQLLogger();
        $entityManager->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        $driver = $this->getDriver();
        $driver->initRepo($entityManager, $classMetadata);
        $driver->truncateIndex();

        $entityManager->getConnection()->getConfiguration()->setSQLLogger($previousLogger);

        $queries = $queryAnalyzer->getExecutedQueries();

        $this->assertTruncateQueries($queries);
    }

    abstract protected function assertInitConfiguration(Configuration $configuration);

    abstract protected function assertTruncateQueries(array $queries);

    /**
     * @return BaseDriver
     */
    abstract protected function getDriver();
}
