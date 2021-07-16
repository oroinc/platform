<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\DependencyInjection\Compiler;

use Doctrine\Common\Cache\CacheProvider;
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
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ConfigurableLoggerPassTest extends WebTestCase
{
    /** @var RequestStack */
    private $requestStack;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();

        $this->requestStack = new RequestStack();
        $this->requestStack->push(new Request());
    }

    public function testLogToCustomChannel()
    {
        $application = new Application(self::$kernel);
        $application->add($this->getLoggerLevelCommand());
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
        static::assertStringContainsString("Log level for global scope is set to 'debug' till", $output);

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
        $logs = $logger->getLogs($this->requestStack->getCurrentRequest());

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
    private function loadYmlFixture(ContainerBuilder $container, string $fixtureFileName)
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

        /** @var CacheProvider $cache */
        $cache = $this->createMock(CacheProvider::class);

        /** @var UserManager $userManager */
        $userManager = $this->createMock(UserManager::class);

        return new LoggerLevelCommand($globalConfigManager, $userConfigManager, $cache, $userManager);
    }
}
