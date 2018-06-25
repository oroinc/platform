<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Command;

use Oro\Bundle\SecurityBundle\Acl\Permission\ConfigurablePermissionProvider;
use Oro\Bundle\SecurityBundle\Command\LoadConfigurablePermissionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigurablePermissionLoadCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigurablePermissionProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $cacheProvider;

    /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $input;

    /** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $output;

    /** @var LoadConfigurablePermissionCommand */
    protected $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->cacheProvider = $this->createMock(ConfigurablePermissionProvider::class);

        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('get')
            ->with('oro_security.acl.configurable_permission_provider')
            ->willReturn($this->cacheProvider);

        $this->command = new LoadConfigurablePermissionCommand();
        $this->command->setContainer($container);
    }

    public function testConfigure()
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertEquals(LoadConfigurablePermissionCommand::NAME, $this->command->getName());
        $this->assertNotEmpty($this->command->getHelp());
    }

    public function testExecute()
    {
        $this->cacheProvider->expects($this->once())->method('buildCache');

        $this->output->expects($this->once())
            ->method('writeln')
            ->with('<info>All configurable permissions successfully loaded into cache.</info>');

        $this->command->run($this->input, $this->output);
    }
}
