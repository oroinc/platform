<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Symfony\Component\Form\Guess\TypeGuess;

use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;

class VariableGuesserTest extends \PHPUnit_Framework_TestCase
{
    /** @var VariableGuesser|\PHPUnit_Framework_MockObject_MockObject */
    protected $guesser;

    /** @var Variable|\PHPUnit_Framework_MockObject_MockObject */
    protected $variable;

    /**
     * Test setup.
     */
    protected function setUp()
    {
        $this->guesser = new VariableGuesser();
        $this->guesser->addFormTypeMapping('string', 'Symfony\Component\Form\Extension\Core\Type\TextType');

        $this->variable = $this->createMock(Variable::class);
    }

    /**
     * Test variable form guessing.
     */
    public function testGuessVariableForm()
    {
        $this->guesser->addFormTypeMapping('testType', 'formTestType', ['formOption' => 'optionValue']);

        $this->variable->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('testType'));

        $this->variable->expects($this->once())
            ->method('getFormOptions')
            ->will($this->returnValue([]));

        $this->variable->expects($this->exactly(2))
            ->method('getLabel')
            ->will($this->returnValue('testLabel'));

        $this->variable->expects($this->exactly(2))
            ->method('getValue')
            ->will($this->returnValue('testValue'));

        $typeGuess = $this->guesser->guessVariableForm($this->variable);

        $this->assertInstanceOf(TypeGuess::class, $typeGuess);
        $this->assertEquals($typeGuess->getType(), 'formTestType');
        $this->assertContains('testLabel', $typeGuess->getOptions());
        $this->assertContains('testValue', $typeGuess->getOptions());
        $this->assertContains('optionValue', $typeGuess->getOptions());
    }

    /**
     * Test guessing with missing type
     */
    public function testGuessWithMissingType()
    {
        $this->variable->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('testType'));

        $typeGuess = $this->guesser->guessVariableForm($this->variable);

        $this->assertNull($typeGuess);
    }

    /**
     * Test guessing without options
     *
     * @dataProvider optionsDataProvider
     * @param string $type
     * @param string $property
     * @param mixed $value
     */
    public function testGuessWithoutOptions($type, $property, $value)
    {
        $this->variable->expects($this->once())
            ->method('getType')
            ->willReturn($type);

        $getter = 'get' . ucfirst($property);
        $this->variable->expects($this->any())
            ->method($getter)
            ->willReturn($value);

        $this->variable->expects($this->once())
            ->method('getFormOptions')
            ->willReturn([]);

        $typeGuess = $this->guesser->guessVariableForm($this->variable);
        $options = $typeGuess->getOptions();

        if (null === $value) {
            $this->assertEmpty($options);
        } else {
            $formOption = ('label' !== $property) ? 'data' : $property;
            $this->assertEquals($value, $options[$formOption]);
        }
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            ['string', 'label', 'test_label'],
            ['string', 'value', 'test_value'],
            ['string', 'label', null],
            ['string', 'value', null]
        ];
    }
}
