<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\Variable;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;
use Symfony\Component\Form\Guess\TypeGuess;

class VariableGuesserTest extends \PHPUnit_Framework_TestCase
{
    /** @var  VariableGuesser|\PHPUnit_Framework_MockObject_MockObject */
    protected $guesser;

    protected function setUp()
    {
        $this->guesser = new VariableGuesser();
    }

    public function testGuessVariableForm()
    {
        $this->guesser->addFormTypeMapping('testType', 'formTestType', ['formOption' => 'optionValue']);

        $variable = $this->createMock(Variable::class);
        $variable->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('testType'));

        $variable->expects($this->once())
            ->method('getFormOptions')
            ->will($this->returnValue([]));

        $variable->expects($this->exactly(2))
            ->method('getLabel')
            ->will($this->returnValue('testLabel'));

        $variable->expects($this->exactly(2))
            ->method('getValue')
            ->will($this->returnValue('testValue'));

        $typeGuess = $this->guesser->guessVariableForm($variable);

        $this->assertInstanceOf(TypeGuess::class, $typeGuess);
        $this->assertEquals($typeGuess->getType(), 'formTestType');
        $this->assertContains('testLabel', $typeGuess->getOptions());
        $this->assertContains('testValue', $typeGuess->getOptions());
        $this->assertContains('optionValue', $typeGuess->getOptions());
    }

    public function testGuessWithMissingType()
    {
        $variable = $this->createMock(Variable::class);
        $variable->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('testType'));

        $typeGuess = $this->guesser->guessVariableForm($variable);

        $this->assertNull($typeGuess);
    }
}
