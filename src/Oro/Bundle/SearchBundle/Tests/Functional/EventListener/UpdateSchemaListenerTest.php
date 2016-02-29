<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class UpdateSchemaListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        if ($this->getContainer()->getParameter('oro_search.engine') !== 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }
    }

    /**
     * @dataProvider commandDataProvider
     * @param string $platform
     * @param string $commandName
     * @param array $params
     * @param string $expectedContent
     */
    public function testCommand($platform, $commandName, array $params, $expectedContent)
    {
        if ($this->getContainer()->getParameter('database_driver') !== $platform) {
            $this->markTestSkipped(sprintf('TestCase for %s only', $platform));
        }

        /** @var KernelInterface $kernel */
        $kernel = $this->getContainer()->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $command = $application->get($commandName);
        $bufferedOutput = new BufferedOutput();

        $this->getContainer()->get('event_dispatcher')->dispatch(
            ConsoleEvents::TERMINATE,
            new ConsoleCommandEvent($command, new ArrayInput($params), $bufferedOutput)
        );

        $this->assertContains($expectedContent, $bufferedOutput);
    }

    /**
     * @return array
     */
    public function commandDataProvider()
    {
        // when we use PostgreSQL, during doctrine:schema:update, doctrine does not delete search index.
        $postgreSQLContent = 'Nothing to update - your database is already in sync with the current entity metadata.';

        return [
            'otherCommand mysql' => [
                'platform' => DatabaseDriverInterface::DRIVER_MYSQL,
                'commandName' => 'doctrine:mapping:info',
                'params' => [],
                'expectedContent' => 'OK',
            ],
            'otherCommand psql' => [
                'platform' => DatabaseDriverInterface::DRIVER_POSTGRESQL,
                'commandName' => 'doctrine:mapping:info',
                'params' => [],
                'expectedContent' => 'OK',
            ],
            'commandWithoutOption mysql' => [
                'platform' => DatabaseDriverInterface::DRIVER_MYSQL,
                'commandName' => 'doctrine:schema:update',
                'params' => [],
                'expectedContent' => 'Please run the operation by passing one - or both - of the following options:',
            ],
            'commandWithoutOption psql' => [
                'platform' => DatabaseDriverInterface::DRIVER_POSTGRESQL,
                'commandName' => 'doctrine:schema:update',
                'params' => [],
                'expectedContent' => $postgreSQLContent,
            ],
            'commandWithAnotherOption mysql' => [
                'platform' => DatabaseDriverInterface::DRIVER_MYSQL,
                'commandName' => 'doctrine:schema:update',
                'params' => ['--dump-sql' => true],
                'expectedContent' => 'ALTER TABLE 1',
            ],
            'commandWithAnotherOption psql' => [
                'platform' => DatabaseDriverInterface::DRIVER_POSTGRESQL,
                'commandName' => 'doctrine:schema:update',
                'params' => ['--dump-sql' => true],
                'expectedContent' => $postgreSQLContent,
            ],
            'commandWithForceOption mysql' => [
                'platform' => DatabaseDriverInterface::DRIVER_MYSQL,
                'commandName' => 'doctrine:schema:update',
                'params' => ['--force' => true],
                'expectedContent' => 'Schema update and create index completed.',
            ],
            'commandWithForceOption' => [
                'platform' => DatabaseDriverInterface::DRIVER_POSTGRESQL,
                'commandName' => 'doctrine:schema:update',
                'params' => ['--force' => true],
                'expectedContent' => $postgreSQLContent,
            ],
        ];
    }
}
