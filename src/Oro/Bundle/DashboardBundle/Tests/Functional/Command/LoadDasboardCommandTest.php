<?php
namespace Oro\Bundle\DashboardBundle\Tests\Unit\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Kernel;

use Oro\Bundle\DashboardBundle\Command\LoadDashboardCommand;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @db_isolation
 * @db_reindex
 */
class ListCommandTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient();
    }

    public function testExecute()
    {
        /** @var Kernel $kernel */
        $kernel = $this->client->getKernel();

        /** @var Application $application */
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $application->add(new LoadDashboardCommand());

        $command       = $application->find(LoadDashboardCommand::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertContains('Load dashboard configuration', $commandTester->getDisplay());
        $this->assertContains('> main', $commandTester->getDisplay());
        $this->assertContains('> quick_launchpad', $commandTester->getDisplay());

        $repository = $kernel->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository('OroDashboardBundle:Dashboard');

        $this->assertNotNull($repository->findOneBy(['name' => 'main']));
        $this->assertNotNull($repository->findOneBy(['name' => 'quick_launchpad']));
    }
}
