<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\EventListener;

use Doctrine\DBAL\Connection;

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
     * @dataProvider commandDataProvider
     */
    public function testCommand($commandName, array $params, $expectedContent, $postgreSQLContent)
    {
        $result = $this->runCommand($commandName, $params);

        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        if ($connection->getParams()['driver'] === 'pdo_pgsql') {
            $expectedContent = $postgreSQLContent;
        }
        $this->assertContains($expectedContent, $result);
    }

    public function commandDataProvider()
    {
        // when we use PostgreSQL, during doctrine:schema:update, doctrine does not delete search index.
        $postgreSQLContent = 'Nothing to update - your database is already in sync with the current entity metadata.';

        return [
            'otherCommand' => [
                'commandName'     => 'doctrine:mapping:info',
                'params'          => [],
                'expectedContent' => 'OK',
                'postgreSQLContent' => 'OK'
            ],
            'commandWithoutOption' => [
                'commandName'     => 'doctrine:schema:update',
                'params'          => [],
                'expectedContent' => 'Please run the operation by passing one - or both - of the following options:',
                'postgreSQLContent' => $postgreSQLContent
            ],
            'commandWithAnotherOption' => [
                'commandName'     => 'doctrine:schema:update',
                'params'          => ['--dump-sql' => true],
                'expectedContent' => 'ALTER TABLE',
                'postgreSQLContent' => $postgreSQLContent
            ],
            'commandWithForceOption' => [
                'commandName'     => 'doctrine:schema:update',
                'params'          => ['--force' => true],
                'expectedContent' => "Schema update and create index completed.",
                'postgreSQLContent' => $postgreSQLContent
            ]
        ];
    }
}
