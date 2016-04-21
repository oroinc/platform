<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\EventListener;

use Doctrine\DBAL\Connection;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class UpdateSchemaListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        if ($this->getContainer()->getParameter('oro_search.engine') != 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }
    }

    /**
     * @dataProvider commandMySqlProvider
     */
    public function testCommandWithMysql($commandName, array $params, $expectedContent)
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        if (false == $connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $this->markTestSkipped('The test has to be run with MySql connection');
        }

        $result = $this->runCommand($commandName, $params);

        $this->assertContains($expectedContent, $result);
    }

    /**
     * @dataProvider commandPostgreSQLProvider
     */
    public function testCommandWithPostgreSql($commandName, array $params, $expectedContent)
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        if (false == $connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $this->markTestSkipped('The test has to be run with PostgreSql connection');
        }

        $result = $this->runCommand($commandName, $params);

        $this->assertContains($expectedContent, $result);
    }

    public function commandMySqlProvider()
    {
        return [
            'otherCommand' => [
                'commandName'     => 'doctrine:mapping:info',
                'params'          => [],
                'expectedContent' => 'OK',
            ],
            'commandWithoutOption' => [
                'commandName'     => 'doctrine:schema:update',
                'params'          => [],
                'expectedContent' => 'Please run the operation by passing one - or both - of the following options:',
            ],
            'commandWithAnotherOption' => [
                'commandName'     => 'doctrine:schema:update',
                'params'          => ['--dump-sql' => true],
                'expectedContent' => 'ALTER TABLE',
            ],
            'commandWithForceOption' => [
                'commandName'     => 'doctrine:schema:update',
                'params'          => ['--force' => true],
                'expectedContent' => "Schema update and create index completed.",
            ]
        ];
    }

    public function commandPostgreSQLProvider()
    {
        // when we use PostgreSQL, during doctrine:schema:update, doctrine does not delete search index.
        $postgreSQLContent = 'Nothing to update - your database is already in sync with the current entity metadata.';

        return [
            'otherCommand' => [
                'commandName'     => 'doctrine:mapping:info',
                'params'          => [],
                'expectedContent' => 'OK'
            ],
            'commandWithoutOption' => [
                'commandName'     => 'doctrine:schema:update',
                'params'          => [],
                'expectedContent' => $postgreSQLContent
            ],
            'commandWithAnotherOption' => [
                'commandName'     => 'doctrine:schema:update',
                'params'          => ['--dump-sql' => true],
                'expectedContent' => $postgreSQLContent
            ],
            'commandWithForceOption' => [
                'commandName'     => 'doctrine:schema:update',
                'params'          => ['--force' => true],
                'expectedContent' => $postgreSQLContent
            ]
        ];
    }
}
