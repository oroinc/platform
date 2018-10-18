<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Command\Provider;

use Oro\Bundle\InstallerBundle\Command\Provider\InputOptionProvider;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class InputOptionProviderTest extends \PHPUnit\Framework\TestCase
{
    const OPTION_NAME = 'option-name';
    const MESSAGE = 'Question message';

    /**
     * @var QuestionHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $questionHelper;

    /**
     * @var InputInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $input;

    /**
     * @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $output;

    /**
     * @var InputOptionProvider
     */
    private $inputOptionProvider;

    protected function setUp()
    {
        parent::setUp();
        $this->questionHelper = $this->createMock(QuestionHelper::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->inputOptionProvider = new InputOptionProvider($this->output, $this->input, $this->questionHelper);
    }

    public function testGetWhenOptionHasValue()
    {
        $defaultOptionValue = 'default option value';
        $this->input
            ->expects($this->any())
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
        $this->input
            ->expects($this->any())
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

    /**
     * @return array
     */
    public function confirmationQuestionDataProvider()
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
        $this->input
            ->expects($this->any())
            ->method('isInteractive')
            ->willReturn(false);

        $this->input
            ->expects($this->any())
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
        $this->input
            ->expects($this->any())
            ->method('isInteractive')
            ->willReturn(true);

        $this->input
            ->expects($this->any())
            ->method('getOption')
            ->with(self::OPTION_NAME)
            ->willReturn(null);

        $this->questionHelper
            ->expects($this->any())
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
        $this->input
            ->expects($this->any())
            ->method('isInteractive')
            ->willReturn(true);

        $this->input
            ->expects($this->any())
            ->method('getOption')
            ->with(self::OPTION_NAME)
            ->willReturn(null);

        $userAnswer = 'user answer';
        $this->questionHelper
            ->expects($this->any())
            ->method('ask')
            ->with($this->input, $this->output, $this->isInstanceOf(Question::class))
            ->willReturn($userAnswer);

        $this->assertEquals(
            $userAnswer,
            $this->inputOptionProvider->get(self::OPTION_NAME, self::MESSAGE)
        );
    }
}
