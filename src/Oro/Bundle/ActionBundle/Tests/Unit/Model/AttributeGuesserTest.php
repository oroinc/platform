<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeGuesser;
use Oro\Bundle\ActionBundle\Provider\DoctrineTypeMappingProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;

class AttributeGuesserTest extends TestCase
{
    private FormRegistry&MockObject $formRegistry;
    private ManagerRegistry&MockObject $managerRegistry;
    private ConfigProvider&MockObject $entityConfigProvider;
    private ConfigProvider&MockObject $formConfigProvider;
    private DoctrineTypeMappingProvider&MockObject $doctrineTypeMappingProvider;
    private AttributeGuesser $guesser;

    #[\Override]
    protected function setUp(): void
    {
        $this->formRegistry = $this->createMock(FormRegistry::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->formConfigProvider = $this->createMock(ConfigProvider::class);
        $this->doctrineTypeMappingProvider = $this->createMock(DoctrineTypeMappingProvider::class);

        $this->guesser = new AttributeGuesser(
            $this->formRegistry,
            $this->managerRegistry,
            $this->entityConfigProvider,
            $this->formConfigProvider
        );
        $this->guesser->setDoctrineTypeMappingProvider($this->doctrineTypeMappingProvider);
    }

    /**
     * @dataProvider guessAttributeFormDataProvider
     */
    public function testGuessAttributeForm(
        ?TypeGuess $expected,
        Attribute $attribute,
        array $formMapping = [],
        array $formConfig = []
    ): void {
        foreach ($formMapping as $mapping) {
            $this->guesser->addFormTypeMapping(
                $mapping['attributeType'],
                $mapping['formType'],
                $mapping['formOptions']
            );
        }

        if ($formConfig) {
            $formConfigId = $this->createMock(FieldConfigId::class);
            $formConfigObject = new Config($formConfigId, $formConfig);
            $this->formConfigProvider->expects($this->once())
                ->method('hasConfig')
                ->with($formConfig['entity'])
                ->willReturn(true);
            $this->formConfigProvider->expects($this->once())
                ->method('getConfig')
                ->with($formConfig['entity'])
                ->willReturn($formConfigObject);
        }

        $this->assertEquals($expected, $this->guesser->guessAttributeForm($attribute));
    }

    public function guessAttributeFormDataProvider(): array
    {
        return [
            'mapping guess' => [
                'expected' => new TypeGuess('checkbox', [], TypeGuess::VERY_HIGH_CONFIDENCE),
                'attribute' => $this->createAttribute('boolean'),
                'formMapping' => [
                    [
                        'attributeType' => 'boolean',
                        'formType' => 'checkbox',
                        'formOptions' => []
                    ]
                ]
            ],
            'configured entity guess' => [
                'expected' => new TypeGuess('test_type', ['key' => 'value'], TypeGuess::VERY_HIGH_CONFIDENCE),
                'attribute' => $this->createAttribute('entity', null, ['class' => 'TestEntity']),
                'formMapping' => [],
                'formConfig' => [
                    'entity' => 'TestEntity',
                    'form_type' => 'test_type',
                    'form_options' => ['key' => 'value']
                ],
            ],
            'regular entity guess' => [
                'expected' => new TypeGuess(
                    EntityType::class,
                    ['class' => 'TestEntity', 'multiple' => false],
                    TypeGuess::VERY_HIGH_CONFIDENCE
                ),
                'attribute' => $this->createAttribute('entity', null, ['class' => 'TestEntity']),
            ],
            'no guess' => [
                'expected' => null,
                'attribute' => $this->createAttribute('array'),
            ],
        ];
    }

    /**
     * @dataProvider guessClassAttributeFormDataProvider
     */
    public function testGuessClassAttributeForm(?TypeGuess $expected, Attribute $attribute): void
    {
        $entityClass = 'TestEntity';
        $fieldName = 'field';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->with($fieldName)
            ->willReturn(true);
        $metadata->expects($this->any())
            ->method('getName')
            ->willReturn($entityClass);
        $this->setEntityMetadata([$entityClass => $metadata]);

        $typeGuesser = $this->getMockForAbstractClass(FormTypeGuesserInterface::class);
        $typeGuesser->expects($this->any())
            ->method('guessType')
            ->with($entityClass, $fieldName)
            ->willReturn($expected);
        $this->formRegistry->expects($this->any())
            ->method('getTypeGuesser')
            ->willReturn($typeGuesser);

        $this->assertEquals($expected, $this->guesser->guessClassAttributeForm($entityClass, $attribute));
    }

    public function guessClassAttributeFormDataProvider(): array
    {
        return [
            'no property path' => [
                'expected' => null,
                'attribute' => $this->createAttribute('array'),
            ],
            'no metadata and field' => [
                'expected' => null,
                'attribute' => $this->createAttribute('array', 'field'),
            ],
            'guess' => [
                'expected' => new TypeGuess('text', [], TypeGuess::VERY_HIGH_CONFIDENCE),
                'attribute' => $this->createAttribute('string', 'entity.field'),
            ]
        ];
    }

    private function createAttribute(string $type, ?string $propertyPath = null, array $options = []): Attribute
    {
        $attribute = new Attribute();
        $attribute->setType($type)
            ->setPropertyPath($propertyPath)
            ->setOptions($options);

        return $attribute;
    }

    private function setEntityMetadata(array $metadataArray): void
    {
        $valueMap = [];
        foreach ($metadataArray as $entity => $metadata) {
            $valueMap[] = [$entity, $metadata];
        }

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($this->isType('string'))
            ->willReturnMap($valueMap);

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->isType('string'))
            ->willReturn($entityManager);
    }
}
