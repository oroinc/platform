<?php
declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Command\Provider;

use Oro\Bundle\InstallerBundle\Command\Provider\InputOptionProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class InputOptionProviderTest extends \PHPUnit\Framework\TestCase
{
    private const OPTION_NAME = 'option-name';
    private const MESSAGE = 'Question message';

    private QuestionHelper|MockObject $questionHelper;
    private InputInterface|MockObject $input;
    private OutputInterface|MockObject $output;
    private InputOptionProvider $inputOptionProvider;

    protected function setUp(): void
    {
        $this->questionHelper = $this->createMock(QuestionHelper::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->inputOptionProvider = new InputOptionProvider($this->output, $this->input, $this->questionHelper);
    }

    public function testGetWhenOptionHasValue()
    {
        $defaultOptionValue = 'default option value';
        $this->input->expects($this->any())
            ->method('getOption')
            ->with(self::OPTION_NAME)
            ->willReturn($defaultOptionValue);

        $this->assertEquals($defaultOptionValue, $this->inputOptionProvider->get(self::OPTION_NAME, self::MESSAGE));
    }

    /**
     * @dataProvider confirmationQuestionDataProvider
     */
    public function testGetWhenOptionHasValueAndConfirmationTypeOfQuestion(string $optionValue, bool $convertedValue)
    {
        $this->input->expects($this->any())
            ->method('getOption')
            ->with(self::OPTION_NAME)
            ->willReturn($optionValue);

        $this->assertEquals(
            $convertedValue,
            $this->inputOptionProvider->get(
                self::OPTION_NAME,
                self::MESSAGE,
                null,
                ['class' => ConfirmationQuestion::class]
            )
        );
    }

    public function confirmationQuestionDataProvider(): array
    {
        return [
            'n equals false as it starts from n' => [
                'optionValue' => 'n',
                'convertedValue' => false
            ],
            'No equals false as it starts from n' => [
                'optionValue' => 'No',
                'convertedValue' => false
            ],
            'no equals false as it starts from n' => [
                'optionValue' => 'no',
                'convertedValue' => false
            ],
            'Yes equals false as it starts from Y' => [
                'optionValue' => 'Yes',
                'convertedValue' => true
            ],
            'y equals false as it starts from y' => [
                'optionValue' => 'y',
                'convertedValue' => true
            ],
            'yes equals false as it starts from y' => [
                'optionValue' => 'yes',
                'convertedValue' => true
            ],
        ];
    }

    public function testGetWhenOptionHasNoValueAndNotInteractive()
    {
        $this->input->expects($this->any())
            ->method('isInteractive')
            ->willReturn(false);

        $this->input->expects($this->any())
            ->method('getOption')
            ->with(self::OPTION_NAME)
            ->willReturn(null);

        $defaultValue = 'some default value';
        $this->assertEquals(
            $defaultValue,
            $this->inputOptionProvider->get(self::OPTION_NAME, self::MESSAGE, $defaultValue)
        );
    }

    public function testGetWhenOptionHasNoValueAndInteractiveAndNoInputFromUser()
    {
        $this->input->expects($this->any())
            ->method('isInteractive')
            ->willReturn(true);

        $this->input->expects($this->any())
            ->method('getOption')
            ->with(self::OPTION_NAME)
            ->willReturn(null);

        $this->questionHelper->expects($this->any())
            ->method('ask')
            ->with($this->input, $this->output, $this->isInstanceOf(Question::class))
            ->willReturn(null);

        $defaultValue = 'some default value';
        $this->assertEquals(
            $defaultValue,
            $this->inputOptionProvider->get(self::OPTION_NAME, self::MESSAGE, $defaultValue)
        );
    }

    public function testGetWhenOptionHasNoValueAndInteractiveAndUserInputsAnswer()
    {
        $this->input->expects($this->any())
            ->method('isInteractive')
            ->willReturn(true);

        $this->input->expects($this->any())
            ->method('getOption')
            ->with(self::OPTION_NAME)
            ->willReturn(null);

        $userAnswer = 'user answer';
        $this->questionHelper->expects($this->any())
            ->method('ask')
            ->with($this->input, $this->output, $this->isInstanceOf(Question::class))
            ->willReturn($userAnswer);

        $this->assertEquals(
            $userAnswer,
            $this->inputOptionProvider->get(self::OPTION_NAME, self::MESSAGE)
        );
    }

    public function testGetCommandParametersFromOptions()
    {
        $options = [
            'option-a' => [
                'label' => 'Option A',
                'options' => [
                    'constructorArgs' => ['default-value-a'],
                    'settings' => [
                        'validator' => [
                            function ($value) {
                            }
                        ]
                    ]
                ],
                'defaultValue' => 'default-value-a',
            ],
            'option-b' => [
                'label' => 'Option B',
                'options' => [
                    'constructorArgs' => ['default-value-b'],
                    'settings' => [
                        'validator' => [
                            function ($value) {
                            }
                        ]
                    ]
                ],
                'defaultValue' => 'default-value-b',
            ]
        ];

        $this->input->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['option-a', null],
                ['option-b', 'some-test-value'],
            ]);

        static::assertEquals(
            [
                '--option-a' => 'default-value-a',
                '--option-b' => 'some-test-value',
            ],
            $this->inputOptionProvider->getCommandParametersFromOptions($options)
        );
    }
}
