<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Command;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Command\ValidateActionConfigurationCommand;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ValidateActionConfigurationCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ValidateActionConfigurationCommand
     */
    protected $command;

    /**
     * @var ConfigurationProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $provider;

    /**
     * @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $container;

    /**
     * @var InputInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $input;

    /**
     * @var OutputStub
     */
    protected $output;

    protected function setUp()
    {
        $this->provider = $this->createMock('Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface');

        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->with('oro_action.configuration.provider.operations', 1)
            ->willReturn($this->provider);

        $this->input = $this->createMock('Symfony\Component\Console\Input\InputInterface');

        $this->output = new OutputStub();

        $this->command = new ValidateActionConfigurationCommand();
        $this->command->setContainer($this->container);
    }

    public function testConfigure()
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider executeProvider
     */
    public function testExecute(array $inputData, array $expectedData)
    {
        $this->provider->expects($this->once())
            ->method('getConfiguration')
            ->with(true, $this->isInstanceOf('Doctrine\Common\Collections\Collection'))
            ->willReturnCallback(
                function ($ignoreCache, Collection $errors) use ($inputData) {
                    foreach ($inputData['configurationErrors'] as $error) {
                        $errors->add($error);
                    }

                    return $inputData['actionConfiguration'];
                }
            );

        $this->command->run($this->input, $this->output);

        $this->assertEquals($expectedData['messages'], $this->output->messages);
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        return [
            'No configuration' => [
                'input' => [
                    'actionConfiguration' => [],
                    'configurationErrors' => [],
                ],
                'expected' => [
                    'messages' => [
                        'Load actions ...',
                        'No actions found.',
                    ],
                ],
            ],
            'No errors' => [
                'input' => [
                    'actionConfiguration' => [
                        'action1' => [],
                        'action2' => [],
                    ],
                    'configurationErrors' => [],
                ],
                'expected' => [
                    'messages' => [
                        'Load actions ...',
                        'Found 2 action(s) with 0 error(s)',
                    ],
                ],
            ],
            '3 errors' => [
                'input' => [
                    'actionConfiguration' => [
                        'action1' => [],
                        'action2' => [],
                    ],
                    'configurationErrors' => [
                        'Error1',
                        'Error2',
                        'Error3',
                    ],
                ],
                'expected' => [
                    'messages' => [
                        'Load actions ...',
                        'Found 2 action(s) with 3 error(s)',
                        'Error1',
                        'Error2',
                        'Error3',
                    ],
                ],
            ],
        ];
    }
}
