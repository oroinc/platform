<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\VariableAssembler;
use Oro\Bundle\WorkflowBundle\Model\VariableManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\AttributeNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\WorkflowVariableNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;

use Oro\Component\Action\Exception\AssemblerException;

class VariablAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $variableNormalizer;

    /**
     * @var \Oro\Bundle\WorkflowBundle\Model\Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflow;

    protected function setUp()
    {
        $attributeNormalizer = $this->createMock(AttributeNormalizer::class);
        $serializer = $this->createMock(WorkflowAwareSerializer::class);

        $this->variableNormalizer = $this->createMock(WorkflowVariableNormalizer::class);
        $this->variableNormalizer->addAttributeNormalizer($attributeNormalizer);
        $this->variableNormalizer->setSerializer($serializer);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $aclManager = $this->createMock(AclManager::class);
        $restrictionManager = $this->createMock(RestrictionManager::class);
        $attributeManager = $this->createMock(AttributeManager::class);
        $variableManager = $this->createMock(VariableManager::class);

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->setMethods(['getName', 'getVariables', 'getDefinition'])
            ->setConstructorArgs([
                $doctrineHelper,
                $aclManager,
                $restrictionManager,
                null,
                $attributeManager,
                null,
                $variableManager,
            ])->getMock();

        $this->workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($this->getWorkflowDefinition());
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     *
     * @param array  $configuration
     * @param string $exception
     * @param string $message
     */
    public function testAssembleRequiredOptionException($configuration, $exception, $message)
    {
        $this->expectException($exception);

        if (false === @preg_match($message, $exception)) {
            $this->expectExceptionMessage($message);
        } else {
            $this->expectExceptionMessageRegExp($message);
        }

        $configuration = [
            'variable_definitions' => [
                'variables' => $configuration,
            ],
        ];

        $assembler = new VariableAssembler($this->variableNormalizer);
        $assembler->assemble($this->workflow, $configuration);
    }

    /**
     * @return WorkflowDefinition|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWorkflowDefinition()
    {
        return $this->createMock(WorkflowDefinition::class);
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider()
    {
        return [
            'no_options' => [
                ['name' => []],
                AssemblerException::class,
                '/Invalid variable type \"\w?\", allowed types are/',
            ],
            'no_type' => [
                ['name' => ['label' => 'test', 'value' => 'test']],
                AssemblerException::class,
                '/Invalid variable type \"\w?\", allowed types are/',
            ],
            'invalid_type' => [
                ['name' => ['label' => 'Label', 'type' => 'text', 'value' => 'text']],
                AssemblerException::class,
                'Invalid variable type "text", allowed types are "bool", "boolean", "int", "integer", ' .
                '"float", "string", "array", "object"',
            ],
            'invalid_type_class' => [
                ['name' => ['label' => 'Label', 'type' => 'string', 'options' => ['class' => 'stdClass']]],
                AssemblerException::class,
                'Option "class" cannot be used in variable "name"',
            ],
            'missing_object_class' => [
                ['name' => ['label' => 'Label', 'type' => 'object']],
                AssemblerException::class,
                'Option "class" is required in variable "name"',
            ],
            'invalid_class' => [
                ['name' => ['label' => 'Label', 'type' => 'object', 'options' => ['class' => 'InvalidClass']]],
                AssemblerException::class,
                'Class "InvalidClass" referenced by "class" option in variable "name" not found',
            ],
        ];
    }

    /**
     * @dataProvider configurationDataProvider
     *
     * @param array    $configuration
     * @param Variable $expectedVariable
     */
    public function testAssemble($configuration, $expectedVariable)
    {
        $configuration = [
            'variable_definitions' => [
                'variables' => $configuration,
            ],
        ];

        $this->variableNormalizer->expects($this->once())
            ->method('denormalizeVariable')
            ->willReturn($expectedVariable->getValue());

        $assembler = new VariableAssembler($this->variableNormalizer);
        $variables = $assembler->assemble($this->workflow, $configuration);

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $variables);
        $this->assertCount(1, $variables);
        $this->assertTrue($variables->containsKey($expectedVariable->getName()));
        $this->assertEquals($expectedVariable, $variables->get($expectedVariable->getName()));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function configurationDataProvider()
    {
        return [
            'string' => [
                ['variable_one' => ['label' => 'label', 'type' => 'string', 'value' => 'text']],
                $this->getVariable('variable_one', 'label', 'string', 'text'),
            ],
            'bool' => [
                ['variable_one' => ['label' => 'label', 'type' => 'bool', 'value' => true]],
                $this->getVariable('variable_one', 'label', 'bool', true),
            ],
            'boolean' => [
                ['variable_one' => ['label' => 'label', 'type' => 'boolean', 'value' => true]],
                $this->getVariable('variable_one', 'label', 'boolean', true),
            ],
            'int' => [
                ['variable_one' => ['label' => 'label', 'type' => 'int', 'value' => 1]],
                $this->getVariable('variable_one', 'label', 'int', 1),
            ],
            'integer' => [
                ['variable_one' => ['label' => 'label', 'type' => 'integer', 'value' => 1]],
                $this->getVariable('variable_one', 'label', 'integer', 1),
            ],
            'float' => [
                ['variable_one' => ['label' => 'label', 'type' => 'float', 'value' => 2.1]],
                $this->getVariable('variable_one', 'label', 'float', 2.1),
            ],
            'array' => [
                ['variable_one' => ['label' => 'label', 'type' => 'array', 'value' => ['text', true, 1, 2.1]]],
                $this->getVariable('variable_one', 'label', 'array', ['text', true, 1, 2.1]),
            ],
            'object' => [
                [
                    'variable_one' => [
                        'label' => 'label',
                        'type' => 'object',
                        'value' => new \stdClass(),
                        'options' => ['class' => 'stdClass'],
                    ],
                ],
                $this->getVariable('variable_one', 'label', 'object', new \stdClass(), ['class' => 'stdClass']),
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $type
     * @param mixed  $value
     * @param array  $options
     *
     * @return Variable
     */
    protected function getVariable($name, $label, $type, $value, array $options = [])
    {
        $variable = new Variable();
        $variable
            ->setName($name)
            ->setLabel($label)
            ->setType($type)
            ->setValue($value)
            ->setOptions($options);

        return $variable;
    }
}
