<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine\Orm;

use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;

use Oro\Bundle\SearchBundle\Engine\Orm\BaseDriver;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\SearchExtensionTrait;

use Doctrine\ORM\Configuration;

abstract class AbstractDriverTest extends WebTestCase
{
    use SearchExtensionTrait;

    protected function setUp()
    {
        $this->initClient();

        if ($this->getContainer()->getParameter('database_driver') !== $this->getDriverName()) {
            $this->markTestSkipped(sprintf('Test doesn\'t run for currently configured DBMS'));
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

    /**
     * @return Query
     */
    private function prepareTestItemQuery()
    {
        $this->loadFixtures([LoadSearchItemData::class], true);
        $this->getSearchIndexer()->reindex(Item::class);

        $query = new Query();
        $query->from('oro_test_item');
        $criteria = new Criteria();
        $query->setCriteria($criteria);

        return $query;
    }

    /**
     * @return array
     */
    public function existsDataProvider()
    {
        return [
            'notExistingValue' => ['notExistingValue', 0],
            'existingValue stringValue' => ['stringValue', LoadSearchItemData::COUNT],
            'existingValue integerValue' => ['integer.integerValue', LoadSearchItemData::COUNT],
        ];
    }

    /**
     * @param string $fieldName
     * @param int $expected
     *
     * @dataProvider existsDataProvider
     */
    public function testFilteringFieldExists($fieldName, $expected)
    {
        $query = $this->prepareTestItemQuery();

        $query->getCriteria()->andWhere(
            Criteria::expr()->exists($fieldName)
        );

        $result = $this->getContainer()->get('oro_search.search.engine')->search($query);

        $this->assertEquals($expected, $result->getRecordsCount());
    }

    /**
     * @return array
     */
    public function notExistsDataProvider()
    {
        return [
            'notExistingValue' => ['notExistingValue', LoadSearchItemData::COUNT],
            'existingValue stringValue' => ['stringValue', 0],
            'existingValue integerValue' => ['integer.integerValue', 0],
        ];
    }

    /**
     * @param string $fieldName
     * @param int $expected
     *
     * @dataProvider notExistsDataProvider
     */
    public function testFilteringFieldNotExists($fieldName, $expected)
    {
        $query = $this->prepareTestItemQuery();

        $query->getCriteria()->andWhere(
            Criteria::expr()->notExists($fieldName)
        );

        $result = $this->getContainer()->get('oro_search.search.engine')->search($query);

        $this->assertEquals($expected, $result->getRecordsCount());
    }

    public function testMultipleFilteringFields()
    {
        $query = $this->prepareTestItemQuery();

        $query->getCriteria()->andWhere(
            Criteria::expr()->andX(
                Criteria::expr()->notExists('notExistingValue1'),
                Criteria::expr()->exists('notExistingValue2')
            )
        );

        $result = $this->getContainer()->get('oro_search.search.engine')->search($query);

        $this->assertEquals(0, $result->getRecordsCount());
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

    /**
     * @return string
     */
    abstract public function getDriverName();
}
