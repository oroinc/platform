<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class SyncCommandTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, SyncCommand::class);
    }

    public function testShouldImplementCronCommandInterface()
    {
        $this->assertClassImplements(CronCommandInterface::class, SyncCommand::class);
    }

    public function testShouldImplementContainerAwareInterface()
    {
        $this->assertClassImplements(ContainerAwareInterface::class, SyncCommand::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new SyncCommand();
    }

    public function testShouldBeExecutedEveryFiveMinutesByCron()
    {
        $command = new SyncCommand();

        $this->assertEquals('*/5 * * * *', $command->getDefaultDefinition());
    }

    public function testShouldAllowSetContainer()
    {
        $container = new Container();

        $command = new SyncCommand();

        $command->setContainer($container);

        $this->assertAttributeSame($container, 'container', $command);
    }
}
