<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Form\WorkflowVariableDataTransformer;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use PHPUnit\Framework\TestCase;

class WorkflowVariableDataTransformerTest extends TestCase
{
    private function createTransformer(array $options = []): WorkflowVariableDataTransformer
    {
        if (array_key_exists('classMetadata', $options)) {
            $classMetadata = $options['classMetadata'];
        } else {
            $classMetadata = $this->createMock(ClassMetadataInfo::class);
            $classMetadata->expects(self::any())
                ->method('getIdentifierFieldNames')
                ->willReturn(['id']);
        }

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);

        $entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $variable = $options['variable'] ?? null;

        return new WorkflowVariableDataTransformer($doctrine, $variable);
    }

    private function createVariable(string $name, string $type, array $options = [], ?string $value = null): Variable
    {
        $variable = new Variable();
        $variable->setName($name);
        $variable->setType($type);
        $variable->setOptions($options);
        $variable->setValue($value);
        $variable->setLabel($name);

        return $variable;
    }

    public function testTransform(): void
    {
        $expected = new \stdClass();
        $expected->public_property = 1;

        $transformer = $this->createTransformer();
        self::assertSame($expected, $transformer->transform($expected));
    }

    /**
     * @dataProvider reverseTransformProvider
     */
    public function testReverseTransform($expected, $entity, array $transformerOptions = []): void
    {
        $transformer = $this->createTransformer($transformerOptions);
        self::assertSame($expected, $transformer->reverseTransform($entity));
    }

    public function reverseTransformProvider(): array
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
}
