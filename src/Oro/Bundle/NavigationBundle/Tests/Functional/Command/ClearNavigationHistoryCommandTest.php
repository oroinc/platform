<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use Oro\Bundle\NavigationBundle\Command\ClearNavigationHistoryCommand;
use Oro\Bundle\NavigationBundle\Entity\Repository\HistoryItemRepository;

class ClearNavigationHistoryCommandTest extends KernelTestCase
{
    public function testExecuteWithNonValidInterval()
    {
        $this->markTestSkipped('Must be redone. See BB-13410');
        $kernel = static::createKernel();
        $kernel->boot();

        $application = new Application($kernel);
        $application->add(new ClearNavigationHistoryCommand());

        $command = $application->find('oro:navigation:history:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--interval' => 'invalid']);

        $output = $commandTester->getDisplay();
        $this->assertContains('Value \'invalid\' should be valid date interval', $output);
    }

    public function testExecuteWithValidInterval()
    {
        $this->markTestSkipped('Must be redone. See BB-13410');
        $kernel = static::createKernel();
        $kernel->boot();

        $application = new Application($kernel);

        /** @var Command $commandMock */
        $commandMock = $this->getMockBuilder(ClearNavigationHistoryCommand::class)
            ->setMethods(['someName'])
            ->getMock();

        $application->add($commandMock);

        $repository = $this->createMock(HistoryItemRepository::class);
        $repository->expects($this->once())
            ->method('clearHistoryItems')
            ->willReturn(5);


        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $kernel->getContainer()->set('doctrine.orm.entity_manager', $entityManager);

        $command = $application->find('oro:navigation:history:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--interval' => '1 day']);

        $output = $commandTester->getDisplay();

        $this->assertContains("5' items deleted from navigation history.", $output);
    }
}
