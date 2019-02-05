<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Functional\Command;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\AssetBundle\NodeProcessFactory;
use Oro\Bundle\RequireJSBundle\Command\OroBuildCommand;
use Oro\Bundle\RequireJSBundle\Manager\ConfigProviderManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class OroBuildCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testExecute(): void
    {
        $application = new Application(self::$kernel);

        $container = self::getContainer();

        /** @var NodeProcessFactory|\PHPUnit\Framework\MockObject\MockObject $nodeProcessFactory */
        $nodeProcessFactory = $this->createMock(NodeProcessFactory::class);
        /** @var ConfigProviderManager|\PHPUnit\Framework\MockObject\MockObject $configProviderManager */
        $configProviderManager = $this->createMock(ConfigProviderManager::class);
        $configProviderManager->expects($this->once())
            ->method('getProviders')
            ->willReturn([]);
        /** @var Filesystem|\PHPUnit\Framework\MockObject\MockObject $fileSystem */
        $fileSystem = $this->createMock(Filesystem::class);
        /** @var string $webRoot */
        $webRoot = $container->getParameter('oro_require_js.web_root');
        /** @var int $buildingTimeout */
        $buildingTimeout = $container->getParameter('oro_require_js.build_timeout');
        /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheProvider::class);
        $cache->expects($this->once())
            ->method('deleteAll');

        $command = new OroBuildCommand(
            $nodeProcessFactory,
            $configProviderManager,
            $fileSystem,
            $webRoot,
            $buildingTimeout,
            $cache
        );

        $application->add($command);

        $command = $application->find('oro:requirejs:build');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName()
        ]);

        $this->assertContains('Clearing the cache', $commandTester->getDisplay());
    }
}
