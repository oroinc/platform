<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\DependencyInjection\Compiler;

use Oro\Bundle\LoggerBundle\Command\LoggerLevelCommand;
use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\DetailedLogsHandlerPass;
use Oro\Bundle\LoggerBundle\DependencyInjection\OroLoggerExtension;
use Oro\Bundle\LoggerBundle\Tests\Functional\Stub\CustomLogChannelCommandStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\MonologBundle\DependencyInjection\Compiler\LoggerChannelPass;
use Symfony\Bundle\MonologBundle\DependencyInjection\MonologExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DetailedLogsHandlerPassTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    public function testLogToCustomChannel()
    {
        $application = new Application(self::$kernel);
        $application->add(new LoggerLevelCommand());
        $application->add(new CustomLogChannelCommandStub());

        $command = $application->find('oro:logger:level');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'level' => 'debug',
            'disable-after' => '5 min'
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertContains("Log level for global scope is set to 'debug' till", $output);

        /** @var CustomLogChannelCommandStub $command */
        $command = $application->find('oro:logger:use-custom-channel');

        $containerBuilder = $this->getContainerBuilder();
        $command->setContainer($containerBuilder);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName()
        ]);

        /** @var Logger $logger */
        $logger = $containerBuilder->get(CustomLogChannelCommandStub::LOGGER_NAME);
        $logs = $logger->getLogs();

        $this->assertNotEmpty($logs);
        $this->assertEquals(CustomLogChannelCommandStub::LOG_MESSAGE, $logs[0]['message']);
        $this->assertEquals('custom_channel', $logs[0]['channel']);
        $this->assertEquals('INFO', $logs[0]['priorityName']);
    }

    /**
     * @return ContainerBuilder
     */
    private function getContainerBuilder()
    {
        $container = new ContainerBuilder();

        $container->getCompilerPassConfig()->setRemovingPasses([]);

        $container->registerExtension(new MonologExtension());
        $container->registerExtension(new OroLoggerExtension());

        $container->addCompilerPass(new LoggerChannelPass());
        $container->addCompilerPass(new DetailedLogsHandlerPass());

        $this->loadYmlFixture($container, 'custom_channels');

        $container->compile();

        return $container;
    }

    /**
     * Loads YAML files service definitions
     *
     * @param ContainerBuilder $container
     * @param string $fixtureFileName
     */
    private function loadYmlFixture(ContainerBuilder $container, string $fixtureFileName)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../Fixtures/yml'));
        $loader->load($fixtureFileName.'.yml');
    }
}
