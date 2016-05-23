<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Command;

use Oro\Bundle\IntegrationBundle\Command\ReverseSyncCommand;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class ReverseSyncCommandTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, ReverseSyncCommand::class);
    }

    public function testShouldImplementContainerAwareInterface()
    {
        $this->assertClassImplements(ContainerAwareInterface::class, ReverseSyncCommand::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new ReverseSyncCommand();
    }

    public function testShouldAllowSetContainer()
    {
        $container = new Container();

        $command = new ReverseSyncCommand();

        $command->setContainer($container);

        $this->assertAttributeSame($container, 'container', $command);
    }
}
