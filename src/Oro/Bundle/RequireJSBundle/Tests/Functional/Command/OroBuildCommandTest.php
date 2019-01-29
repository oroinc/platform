<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Functional\Command;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\RequireJSBundle\Command\OroBuildCommand;
use Oro\Bundle\RequireJSBundle\DependencyInjection\Compiler\ConfigProviderCompilerPass;
use Oro\Bundle\RequireJSBundle\Manager\ConfigProviderManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class OroBuildCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testExecute(): void
    {
        /** @var ConfigProviderManager|\PHPUnit_Framework_MockObject_MockObject $configProviderManager */
        $configProviderManager = $this->createMock(ConfigProviderManager::class);
        $configProviderManager->expects($this->once())
            ->method('getProviders')
            ->willReturn([]);
        $this->getContainer()->set(ConfigProviderCompilerPass::PROVIDER_SERVICE, $configProviderManager);

        $application = new Application(self::$kernel);

        $command = new OroBuildCommand();

        /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(CacheProvider::class);
        $cache->expects($this->once())
            ->method('deleteAll');
        $command->setCache($cache);

        $application->add($command);

        $command = $application->find('oro:requirejs:build');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName()
        ]);

        $this->assertContains('Clearing the cache', $commandTester->getDisplay());
    }
}
