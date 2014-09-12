<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\EventListener;

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
    public function testCommand($commandName, array $params, $expectedContent)
    {
        $result = $this->runCommand($commandName, $params);
        $this->assertContains($expectedContent, $result);
    }

    public function commandDataProvider()
    {
        return [
            'otherCommand' => [
                'commandName'     => 'doctrine:mapping:info',
                'params'          => [],
                'expectedContent' => 'OK'
            ],
            'commandWithoutOption' => [
                'commandName'     => 'doctrine:schema:update',
                'params'          => [],
                'expectedContent' => 'Please run the operation by passing one - or both - of the following options:'
            ],
            'commandWithAnotherOption' => [
                'commandName'     => 'doctrine:schema:update',
                'params'          => ['--dump-sql' => true],
                'expectedContent' => 'ALTER TABLE'
            ],
            'commandWithForceOption' => [
                'commandName'     => 'doctrine:schema:update',
                'params'          => ['--force' => true],
                'expectedContent' => "Schema update and create index completed."
            ]
        ];
    }
}
