<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\WorkflowBundle\Form\WorkflowVariableDataTransformer;
use Oro\Bundle\WorkflowBundle\Model\Variable;

class WorkflowVariableDataTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $options
     *
     * @return WorkflowVariableDataTransformer
     */
    protected function createTransformer(array $options = [])
    {
        if (array_key_exists('classMetadata', $options)) {
            $classMetadata = $options['classMetadata'];
        } else {
            $classMetadata = $this->createMock(ClassMetadataInfo::class);
            $classMetadata->expects($this->any())
                ->method('getIdentifierFieldNames')
                ->willReturn(['id']);
        }

        $entityManager = $this->createMock(EntityManager::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $variable = isset($options['variable']) ? $options['variable'] : null;

        return new WorkflowVariableDataTransformer($managerRegistry, $variable);
    }

    public function testTransform()
    {
        $expected = new \stdClass();
        $expected->public_property = 1;

        $transformer = $this->createTransformer();
        $this->assertSame($expected, $transformer->transform($expected));
    }

    /**
     * @dataProvider reverseTransformProvider
     *
     * @param       $expected
     * @param       $entity
     * @param array $transformerOptions
     */
    public function testReverseTransform($expected, $entity, array $transformerOptions = [])
    {
        $transformer = $this->createTransformer($transformerOptions);
        $this->assertSame($expected, $transformer->reverseTransform($entity));
    }

    /**
     * @return array
     */
    public function reverseTransformProvider()
    {
        return [
            'no_data' => [
                'expected' => '',
                'entity' => null
            ],
            'no_class_metadata' => [
                'expected' => '',
                'entity' => (object) ['id' => 1],
                'options' => [
                    'classMetadata' => null
                ]
            ],
            'no_identifier_or_variable' => [
                'expected' => 1,
                'entity' => (object) ['id' => 1],
            ],
            'variable_without_identifier' => [
                'expected' => 1,
                'entity' => (object) ['id' => 1],
                'options' => [
                    'variable' => $this->createVariable('entity_var', 'entity', ['class' => 'stdClass'])
                ]
            ],
            'variable_with_identifier' => [
                'expected' => 1,
                'entity' => (object) ['code' => 1],
                'options' => [
                    'variable' => $this->createVariable(
                        'entity_var',
                        'entity',
                        [
                            'class' => 'stdClass',
                            'identifier' => 'code'
                        ]
                    )
                ]
            ],
            'variable_with_wrong_identifier' => [
                'expected' => '',
                'entity' => (object) ['code' => 1],
                'options' => [
                    'variable' => $this->createVariable(
                        'entity_var',
                        'entity',
                        [
                            'class' => 'stdClass',
                            'identifier' => 'id'
                        ]
                    )
                ]
            ]
        ];
    }

    /**
     * @param string $name
     * @param string $type
     * @param array  $options
     * @param string $value
     *
     * @return Variable
     */
    protected function createVariable($name, $type, array $options = [], $value = null)
    {
        $variable = new Variable();
        $variable
            ->setName($name)
            ->setType($type)
            ->setOptions($options)
            ->setValue($value)
            ->setLabel($name);

        return $variable;
    }
}
