<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Command;

use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1;
use Oro\Component\ConfigExpression\FactoryWithTypesInterface;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractDebugCommandTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var FactoryWithTypesInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $factory;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $input;

    /** @var ContainerAwareCommand */
    protected $command;

    /** @var OutputStub */
    protected $output;

    protected function setUp()
    {
        $this->factory = $this->createMock(FactoryWithTypesInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = new OutputStub();
        $this->command = $this->getCommandInstance();
        $this->command->setContainer($this->container);
    }

    /**
     * @param array $types
     * @param array $expected
     * @param string|null $argument
     * @param \TypeError|\ErrorException $exception
     *
     * @dataProvider executeProvider
     */
    public function testExecute(array $types, array $expected, $argument = null, $exception = null)
    {
        $this->factory->expects($this->once())->method('getTypes')->willReturn($types);

        $this->input->expects($this->once())->method('getArgument')->willReturn($argument);

        $this->container->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function ($serviceId) use ($exception) {
                    if ($serviceId === $this->getFactoryServiceId()) {
                        return $this->factory;
                    }

                    if ($exception) {
                        throw $exception;
                    }

                    return new TestEntity1();
                }
            );

        $this->command->run($this->input, $this->output);

        $outputContent = implode("\n", $this->output->messages);
        foreach ($expected as $message) {
            $this->assertContains($message, $outputContent);
        }
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        return [
            'no types' => [
                'types' => [],
                'expected' => [
                    'Short Description',
                ],
            ],
            'with types' => [
                'types' => [
                    'name1' => 'type1',
                    'name2' => 'type2',
                ],
                'expected' => [
                    'Short Description',
                    'name1',
                    'name2',
                    'This is description',
                    'of the class',
                ],
            ],
            'no types with argument' => [
                'types' => [],
                'expected' => [
                    'Type "name1" is not found',
                ],
                'argument' => 'name1',
            ],
            'with types with argument' => [
                'types' => [
                    'name1' => 'type1',
                    'name2' => 'type2',
                ],
                'expected' => [
                    'Full Description',
                    'name1',
                    'type1',
                    'Class TestEntity1',
                ],
                'argument' => 'name1',
            ],
            'type error exception' => [
                'types' => [
                    'name1' => 'type1',
                    'name2' => 'type2',
                ],
                'expected' => [
                    'Can not load Service "type1": test message1',
                    'Short Description'
                ],
                'argument' => null,
                'exception' => new \TypeError('test message1')
            ],
            'error exception' => [
                'types' => [
                    'name1' => 'type1',
                    'name2' => 'type2',
                ],
                'expected' => [
                    'Can not load Service "type1": test message2',
                    'Short Description'
                ],
                'argument' => null,
                'exception' => new \ErrorException('test message2')
            ],
        ];
    }

    /**
     * @return string
     */
    abstract protected function getFactoryServiceId();

    /**
     * @return string
     */
    abstract protected function getArgumentName();

    /**
     * @return ContainerAwareCommand
     */
    abstract protected function getCommandInstance();
}
