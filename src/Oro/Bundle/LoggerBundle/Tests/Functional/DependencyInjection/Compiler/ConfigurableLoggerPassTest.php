<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\DependencyInjection\Compiler;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\Command\LoggerLevelCommand;
use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\ConfigurableLoggerPass;
use Oro\Bundle\LoggerBundle\DependencyInjection\OroLoggerExtension;
use Oro\Bundle\LoggerBundle\Tests\Functional\Stub\CustomLogChannelCommandStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Monolog\Processor\DebugProcessor;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\MonologBundle\DependencyInjection\Compiler\LoggerChannelPass;
use Symfony\Bundle\MonologBundle\DependencyInjection\MonologExtension;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ConfigurableLoggerPassTest extends WebTestCase
{
    private RequestStack $requestStack;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();

        $this->requestStack = new RequestStack();
        $this->requestStack->push(new Request());
    }

    public function testLogToCustomChannel(): void
    {
        $application = new Application(self::$kernel);
        $application->add($this->getLoggerLevelCommand());

        $containerBuilder = $this->getContainerBuilder();
        /** @var Logger $logger */
        $logger = $containerBuilder->get(CustomLogChannelCommandStub::LOGGER_NAME);

        $application->add(new CustomLogChannelCommandStub($logger));

        $command = $application->find('oro:logger:level');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'level' => 'debug',
            'disable-after' => '5 min'
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        self::assertStringContainsString("Log level for global scope is set to 'debug' till", $output);

        /** @var CustomLogChannelCommandStub $command */
        $command = $application->find('oro:logger:use-custom-channel');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName()
        ]);

        $logs = $logger->getLogs($this->requestStack->getCurrentRequest());

        self::assertNotEmpty($logs);
        self::assertEquals(CustomLogChannelCommandStub::LOG_MESSAGE, $logs[0]['message']);
        self::assertEquals('custom_channel', $logs[0]['channel']);
        self::assertEquals('INFO', $logs[0]['priorityName']);
    }

    private function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->getCompilerPassConfig()->setRemovingPasses([]);

        $container->registerExtension(new MonologExtension());
        $container->registerExtension(new OroLoggerExtension());

        $container->addCompilerPass(new LoggerChannelPass());
        $container->addCompilerPass(new ConfigurableLoggerPass());

        $this->loadYmlFixture($container, 'custom_channels');

        $container->compile();

        $container->get(CustomLogChannelCommandStub::LOGGER_NAME)
            ->pushProcessor(new DebugProcessor($this->requestStack));

        return $container;
    }

    /**
     * Loads YAML files service definitions
     */
    private function loadYmlFixture(ContainerBuilder $container, string $fixtureFileName): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../Fixtures/yml'));
        $loader->load($fixtureFileName.'.yml');
    }

    private function getLoggerLevelCommand(): LoggerLevelCommand
    {
        /** @var ConfigManager $globalConfigManager */
        $globalConfigManager = $this->createMock(ConfigManager::class);

        /** @var ConfigManager $userConfigManager */
        $userConfigManager = $this->createMock(ConfigManager::class);

        /** @var ArrayAdapter $cache */
        $cache = $this->createMock(ArrayAdapter::class);

        /** @var UserManager $userManager */
        $userManager = $this->createMock(UserManager::class);

        return new LoggerLevelCommand($globalConfigManager, $userConfigManager, $cache, $userManager);
    }
}
