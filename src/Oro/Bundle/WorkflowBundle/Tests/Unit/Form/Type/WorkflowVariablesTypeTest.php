<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Guess\TypeGuess;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowVariablesType;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class WorkflowVariablesTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkflowVariablesType
     */
    protected $type;

    /**
     * @var VariableGuesser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $variableGuesser;

    protected function setUp()
    {
        $this->variableGuesser = $this->createMock(VariableGuesser::class);

        $this->type = new WorkflowVariablesType($this->variableGuesser);
    }

    public function testBuildForm()
    {
        $workflow = $this->createMock(Workflow::class);
        $variable = $this->createMock(Variable::class);
        $workflow->expects($this->once())
            ->method('getVariables')
            ->with(true)
            ->will($this->returnValue([$variable]));
        $variable->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('variableName'));
        $typeGuess = $this->createMock(TypeGuess::class);
        $this->variableGuesser->expects($this->once())
            ->method('guessVariableForm')
            ->with($variable)
            ->will($this->returnValue($typeGuess));
        $typeGuess->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(TextType::class));
        $typeGuess->expects($this->once())
            ->method('getOptions')
            ->will($this->returnValue(['label' => 'testLabel']));
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('add')
            ->with('variableName', TextType::class, ['label' => 'testLabel']);

        $this->type->buildForm($builder, ['workflow' => $workflow]);
    }
}
