<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Command;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionHandlerInterface;
use Oro\Bundle\CacheBundle\Command\InvalidateCacheScheduleCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InvalidateCacheScheduleCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigure(): void
    {
        $command = new InvalidateCacheScheduleCommand($this->createMock(ContainerInterface::class));

        self::assertSame('oro:cache:invalidate:schedule', $command->getName());

        $arguments = $command->getDefinition()->getArguments();

        self::assertSame('service', $arguments['service']->getName());
        self::assertTrue($arguments['service']->isRequired());

        self::assertSame('parameters', $arguments['parameters']->getName());
        self::assertFalse($arguments['parameters']->isRequired());
    }

    public function testExecute(): void
    {
        $serviceDefinition = 'service.definition';
        $parameters = [
            'test' => 'string',
            'test2' => 32,
        ];

        $service = $this->createMock(InvalidateCacheActionHandlerInterface::class);
        $service->expects(self::once())
            ->method('handle')
            ->with(new InvalidateCacheDataStorage($parameters));

        $application = $this->buildApplicationWithService();

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('get')
            ->with($serviceDefinition)
            ->willReturn($service);

        $command = new InvalidateCacheScheduleCommand($container);
        $command->setApplication($application);

        $inputDefinition = new InputDefinition();
        $inputDefinition->addArgument(new InputArgument('service'));
        $inputDefinition->addArgument(new InputArgument('parameters'));

        $input = new ArrayInput(
            [
                'service' => $serviceDefinition,
                'parameters' => serialize($parameters),
            ],
            $inputDefinition
        );

        $command->execute($input, new NullOutput());
    }

    private function buildApplicationWithService(): Application
    {
        $application = $this->createMock(Application::class);
        $application->expects(self::once())
            ->method('getHelperSet')
            ->willReturn(new HelperSet());

        return $application;
    }
}
