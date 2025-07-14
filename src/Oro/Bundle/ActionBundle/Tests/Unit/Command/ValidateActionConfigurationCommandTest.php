<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Command;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Command\ValidateActionConfigurationCommand;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationValidatorInterface;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;

class ValidateActionConfigurationCommandTest extends TestCase
{
    private ConfigurationProviderInterface&MockObject $provider;
    private ConfigurationValidatorInterface&MockObject $validator;
    private InputInterface&MockObject $input;
    private OutputStub $output;
    private ValidateActionConfigurationCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = $this->createMock(ConfigurationProviderInterface::class);
        $this->validator = $this->createMock(ConfigurationValidatorInterface::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = new OutputStub();

        $this->command = new ValidateActionConfigurationCommand($this->provider, $this->validator);
    }

    public function testConfigure(): void
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
    }

    /**
     * @dataProvider executeProvider
     */
    public function testExecute(array $inputData, array $expectedData): void
    {
        $this->provider->expects($this->once())
            ->method('getConfiguration')
            ->willReturnCallback(function () use ($inputData) {
                return $inputData['actionConfiguration'];
            });
        $this->validator->expects($this->any())
            ->method('validate')
            ->willReturnCallback(function ($configuration, Collection $errors) use ($inputData) {
                $this->assertEquals($inputData['actionConfiguration'], $configuration);
                foreach ($inputData['configurationErrors'] as $error) {
                    $errors->add($error);
                }
            });

        $this->command->run($this->input, $this->output);

        $this->assertEquals($expectedData['messages'], $this->output->messages);
    }

    public function executeProvider(): array
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
