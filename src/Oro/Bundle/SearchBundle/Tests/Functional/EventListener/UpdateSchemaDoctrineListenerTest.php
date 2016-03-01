<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class UpdateSchemaDoctrineListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        if ($this->getContainer()->getParameter('oro_search.engine') !== 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }
    }

    protected function tearDown()
    {
        $this->getContainer()->get('oro_search.fulltext_index_manager')->createIndexes();

        parent::tearDown();
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
        $databaseDriver = $this->getContainer()->getParameter('database_driver');
        if ($databaseDriver !== $platform) {
            $this->markTestSkipped(sprintf('TestCase for %s only', $platform));
        }

        // emulate doctrine:schema:update drops index
        if ($databaseDriver === DatabaseDriverInterface::DRIVER_MYSQL) {
            /** @var EntityManagerInterface $em */
            $em = $this->getContainer()->get('doctrine')->getManager('search');
            $em->getConnection()->executeQuery('DROP INDEX value ON oro_search_index_text');
        }

        /** @var KernelInterface $kernel */
        $kernel = $this->getContainer()->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $application->doRun(new ArrayInput(['help']), new BufferedOutput());

        $command = $application->find($commandName);

        $output = new BufferedOutput();
        $input = new ArrayInput($params, $command->getDefinition());

        $this->getContainer()->get('event_dispatcher')->dispatch(
            ConsoleEvents::TERMINATE,
            new ConsoleTerminateEvent($command, $input, $output, 0)
        );

        $this->assertEquals($expectedContent, $output->fetch());
    }

    /**
     * @return array
     */
    public function commandDataProvider()
    {
        return [
            'otherCommand mysql' => [
                'platform' => DatabaseDriverInterface::DRIVER_MYSQL,
                'commandName' => 'doctrine:mapping:info',
                'params' => [],
                'expectedContent' => '',
            ],
            'otherCommand psql' => [
                'platform' => DatabaseDriverInterface::DRIVER_POSTGRESQL,
                'commandName' => 'doctrine:mapping:info',
                'params' => [],
                'expectedContent' => '',
            ],
            'commandWithoutOption mysql' => [
                'platform' => DatabaseDriverInterface::DRIVER_MYSQL,
                'commandName' => 'doctrine:schema:update',
                'params' => [],
                'expectedContent' => '',
            ],
            'commandWithoutOption psql' => [
                'platform' => DatabaseDriverInterface::DRIVER_POSTGRESQL,
                'commandName' => 'doctrine:schema:update',
                'params' => [],
                'expectedContent' => '',
            ],
            'commandWithAnotherOption mysql' => [
                'platform' => DatabaseDriverInterface::DRIVER_MYSQL,
                'commandName' => 'doctrine:schema:update',
                'params' => ['--dump-sql' => true],
                'expectedContent' => '',
            ],
            'commandWithAnotherOption psql' => [
                'platform' => DatabaseDriverInterface::DRIVER_POSTGRESQL,
                'commandName' => 'doctrine:schema:update',
                'params' => ['--dump-sql' => true],
                'expectedContent' => '',
            ],
            'commandWithForceOption mysql' => [
                'platform' => DatabaseDriverInterface::DRIVER_MYSQL,
                'commandName' => 'doctrine:schema:update',
                'params' => ['--force' => true],
                'expectedContent' => "Schema update and create index completed.\nIndexes were created.\n",
            ],
            'commandWithForceOption' => [
                'platform' => DatabaseDriverInterface::DRIVER_POSTGRESQL,
                'commandName' => 'doctrine:schema:update',
                'params' => ['--force' => true],
                'expectedContent' => "Schema update and create index completed.\n",
            ],
        ];
    }
}
