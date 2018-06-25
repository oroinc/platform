<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Action\Handler;

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
use Symfony\Component\HttpKernel\KernelInterface;

class InvalidateCacheScheduleCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigure()
    {
        $command = new InvalidateCacheScheduleCommand();

        static::assertSame('oro:cache:invalidate:schedule', $command->getName());

        $arguments = $command->getDefinition()->getArguments();

        static::assertSame('service', $arguments['service']->getName());
        static::assertTrue($arguments['service']->isRequired());

        static::assertSame('parameters', $arguments['parameters']->getName());
        static::assertFalse($arguments['parameters']->isRequired());
    }

    public function testExecute()
    {
        $serviceDefinition = 'service.definition';
        $parameters = [
            'test' => 'string',
            'test2' => 32,
        ];

        $service = $this->createMock(InvalidateCacheActionHandlerInterface::class);
        $service->expects(static::once())
            ->method('handle')
            ->with(new InvalidateCacheDataStorage($parameters));

        $application = $this->buildApplicationWithService($serviceDefinition, $service);

        $command = new InvalidateCacheScheduleCommand();
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

    /**
     * @param string $serviceDefinition
     * @param object $service
     *
     * @return Application|\PHPUnit\Framework\MockObject\MockObject
     */
    private function buildApplicationWithService($serviceDefinition, $service)
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(static::once())
            ->method('get')
            ->with($serviceDefinition)
            ->willReturn($service);

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(static::once())
            ->method('getContainer')
            ->willReturn($container);

        $application = $this->createMock(Application::class);
        $application->expects(static::once())
            ->method('getHelperSet')
            ->willReturn(new HelperSet());
        $application->expects(static::once())
            ->method('getKernel')
            ->willReturn($kernel);

        return $application;
    }
}
