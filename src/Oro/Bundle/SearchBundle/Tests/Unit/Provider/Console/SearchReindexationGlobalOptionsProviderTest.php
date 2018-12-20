<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Provider\Console;

use Oro\Bundle\InstallerBundle\Command\PlatformUpdateCommand;
use Oro\Bundle\PlatformBundle\Command\HelpCommand;
use Oro\Bundle\SearchBundle\Provider\Console\SearchReindexationGlobalOptionsProvider;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class SearchReindexationGlobalOptionsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Command|\PHPUnit\Framework\MockObject\MockObject */
    protected $command;

    /** @var SearchReindexationGlobalOptionsProvider */
    protected $provider;

    protected function setUp()
    {
        $this->command = $this->createMock(Command::class);

        $this->provider = new SearchReindexationGlobalOptionsProvider();
    }

    public function testAddGlobalOptionsNotSupportedCommand()
    {
        $this->command->expects($this->once())
            ->method('getName')
            ->willReturn('some:command');
        $this->command->expects($this->never())
            ->method('getApplication');
        $this->command->expects($this->never())
            ->method('getDefinition');

        $this->provider->addGlobalOptions($this->command);
    }

    public function testAddGlobalOptions()
    {
        $definition = $this->prepareDefinition();
        $application = $this->prepareApplication($definition);

        $this->command->expects($this->once())
            ->method('getName')
            ->willReturn(PlatformUpdateCommand::NAME);
        $this->command->expects($this->once())
            ->method('getApplication')
            ->willReturn($application);
        $this->command->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $this->provider->addGlobalOptions($this->command);
    }

    public function testAddGlobalOptionsHelp()
    {
        $definition = $this->prepareDefinition();
        $application = $this->prepareApplication($definition);

        $this->command->expects($this->once())
            ->method('getName')
            ->willReturn(PlatformUpdateCommand::NAME);

        $command = $this->createMock(HelpCommand::class);
        $command->expects($this->once())
            ->method('getApplication')
            ->willReturn($application);
        $command->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);
        $command->expects($this->atLeastOnce())->method('getCommand')->willReturn($this->command);

        $this->provider->addGlobalOptions($command);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|InputDefinition
     */
    protected function prepareDefinition()
    {
        $option1 = new InputOption(
            SearchReindexationGlobalOptionsProvider::SKIP_REINDEXATION_OPTION_NAME,
            null,
            InputOption::VALUE_NONE,
            'Determines whether search data reindexation need to be triggered or not'
        );
        $option2 = new InputOption(
            SearchReindexationGlobalOptionsProvider::SCHEDULE_REINDEXATION_OPTION_NAME,
            null,
            InputOption::VALUE_NONE,
            'Determines whether search data reindexation need to be scheduled or not'
        );

        /** @var InputDefinition|\PHPUnit\Framework\MockObject\MockObject $definition */
        $definition = $this->createMock(InputDefinition::class);
        $definition->expects($this->exactly(4))
            ->method('addOption')
            ->withConsecutive(
                [$option1],
                [$option1],
                [$option2],
                [$option2]
            );

        return $definition;
    }

    /**
     * @param $definition
     * @return \PHPUnit\Framework\MockObject\MockObject|Application
     */
    protected function prepareApplication($definition)
    {
        /** @var Application|\PHPUnit\Framework\MockObject\MockObject $application */
        $application = $this->createMock(Application::class);
        $application->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        return $application;
    }
}
