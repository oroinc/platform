<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\EventListener\InstallCommandDeploymentTypeListener;
use Oro\Bundle\InstallerBundle\InstallerEvent;
use Oro\Component\Testing\Command\CommandOutputNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

class InstallCommandDeploymentTypeListenerTest extends TestCase
{
    public function testAfterDatabasePreparationWhenDeploymentTypeIsDefinedButConfigFileIsNotProvided()
    {
        $event = $this->createMock(InstallerEvent::class);

        $input = $this->createMock(InputInterface::class);
        $event->expects($this->once())
            ->method('getInput')
            ->willReturn($input);

        $output = new BufferedOutput();
        $event->expects($this->once())
            ->method('getOutput')
            ->willReturn($output);

        $listener = new InstallCommandDeploymentTypeListener(
            __DIR__.'/fixtures/without_deployment_type_config_file',
            'staging'
        );
        $listener->afterDatabasePreparation($event);
        $content = $output->fetch();

        self::assertStringContainsString(
            '[WARNING] Deployment config "./config/deployment/config_staging.yml"' .
            ' for deployment type "staging" not found.',
            CommandOutputNormalizer::toSingleLine($content)
        );
    }

    public function testAfterDatabasePreparationWhenDeploymentTypeIsDefined()
    {
        $event = $this->createMock(InstallerEvent::class);

        $event->expects($this->never())
            ->method('getInput');

        $event->expects($this->never())
            ->method('getOutput');

        $listener = new InstallCommandDeploymentTypeListener(
            __DIR__.'/fixtures/with_deployment_type_config_file',
            'staging'
        );
        $listener->afterDatabasePreparation($event);
    }

    public function testAfterDatabasePreparationWhenDeploymentTypeIsNull()
    {
        $event = $this->createMock(InstallerEvent::class);
        $listener = new InstallCommandDeploymentTypeListener(__DIR__, null);
        $listener->afterDatabasePreparation($event);
    }
}
