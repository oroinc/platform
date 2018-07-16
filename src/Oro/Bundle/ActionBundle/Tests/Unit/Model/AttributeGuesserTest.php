<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeGuesser;
use Oro\Bundle\ActionBundle\Provider\DoctrineTypeMappingProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\Guess\TypeGuess;

class AttributeGuesserTest extends \PHPUnit\Framework\TestCase
{
    /* @var AttributeGuesser */
    protected $guesser;

    /* @var \PHPUnit\Framework\MockObject\MockObject|FormRegistry */
    protected $formRegistry;

    /* @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $managerRegistry;

    /* @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    protected $entityConfigProvider;

    /* @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    protected $formConfigProvider;

    /* @var DoctrineTypeMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineTypeMappingProvider;

    protected function setUp()
    {
        $this->formRegistry = $this->getMockBuilder('Symfony\Component\Form\FormRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry
            = $this->getMockForAbstractClass('Doctrine\Common\Persistence\ManagerRegistry');

        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineTypeMappingProvider = $this->createMock(DoctrineTypeMappingProvider::class);

        $this->guesser = new AttributeGuesser(
            $this->formRegistry,
            $this->managerRegistry,
            $this->entityConfigProvider,
            $this->formConfigProvider
        );
        $this->guesser->setDoctrineTypeMappingProvider($this->doctrineTypeMappingProvider);
    }

    protected function tearDown()
    {
        unset(
            $this->formRegistry,
            $this->managerRegistry,
            $this->entityConfigProvider,
            $this->guesser,
            $this->formConfigProvider,
            $this->doctrineTypeMappingProvider
        );
    }

    /**
     * @param TypeGuess|null $expected
     * @param Attribute $attribute
     * @param array $formMapping
     * @param array $formConfig
     * @dataProvider guessAttributeFormDataProvider
     */
    public function testGuessAttributeForm(
        $expected,
        Attribute $attribute,
        array $formMapping = array(),
        array $formConfig = array()
    ) {
        foreach ($formMapping as $mapping) {
            $this->guesser->addFormTypeMapping(
                $mapping['attributeType'],
                $mapping['formType'],
                $mapping['formOptions']
            );
        }

        if ($formConfig) {
            $formConfigId = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
                ->disableOriginalConstructor()
                ->getMock();
            $formConfigObject = new Config($formConfigId, $formConfig);
            $this->formConfigProvider->expects($this->once())->method('hasConfig')
                ->with($formConfig['entity'])->will($this->returnValue(true));
            $this->formConfigProvider->expects($this->once())->method('getConfig')
                ->with($formConfig['entity'])->will($this->returnValue($formConfigObject));
        }

        $this->assertEquals($expected, $this->guesser->guessAttributeForm($attribute));
    }

    public function guessAttributeFormDataProvider()
    {
        return array(
            'mapping guess' => array(
                'expected' => new TypeGuess('checkbox', array(), TypeGuess::VERY_HIGH_CONFIDENCE),
                'attribute' => $this->createAttribute('boolean'),
                'formMapping' => array(
                    array(
                        'attributeType' => 'boolean',
                        'formType' => 'checkbox',
                        'formOptions' => array()
                    )
                )
            ),
            'configured entity guess' => array(
                'expected' => new TypeGuess('test_type', array('key' => 'value'), TypeGuess::VERY_HIGH_CONFIDENCE),
                'attribute' => $this->createAttribute('entity', null, array('class' => 'TestEntity')),
                'formMapping' => array(),
                'formConfig' => array(
                    'entity' => 'TestEntity',
                    'form_type' => 'test_type',
                    'form_options' => array('key' => 'value')
                ),
            ),
            'regular entity guess' => array(
                'expected' => new TypeGuess(
                    EntityType::class,
                    array('class' => 'TestEntity', 'multiple' => false),
                    TypeGuess::VERY_HIGH_CONFIDENCE
                ),
                'attribute' => $this->createAttribute('entity', null, array('class' => 'TestEntity')),
            ),
            'no guess' => array(
                'expected' => null,
                'attribute' => $this->createAttribute('array'),
            ),
        );
    }

    /**
     * @param string $expected
     * @param Attribute $attribute
     * @dataProvider guessClassAttributeFormDataProvider
     */
    public function testGuessClassAttributeForm($expected, Attribute $attribute)
    {
        $entityClass = 'TestEntity';
        $fieldName = 'field';

        $metadata = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->any())->method('hasAssociation')->with($fieldName)
            ->will($this->returnValue(true));
        $metadata->expects($this->any())->method('getName')
            ->will($this->returnValue($entityClass));
        $this->setEntityMetadata(array($entityClass => $metadata));

        $typeGuesser = $this->getMockForAbstractClass('Symfony\Component\Form\FormTypeGuesserInterface');
        $typeGuesser->expects($this->any())->method('guessType')->with($entityClass, $fieldName)
            ->will($this->returnValue($expected));
        $this->formRegistry->expects($this->any())->method('getTypeGuesser')
            ->will($this->returnValue($typeGuesser));

        $this->assertEquals($expected, $this->guesser->guessClassAttributeForm($entityClass, $attribute));
    }

    public function guessClassAttributeFormDataProvider()
    {
        return array(
            'no property path' => array(
                'expected' => null,
                'attribute' => $this->createAttribute('array'),
            ),
            'no metadata and field' => array(
                'expected' => null,
                'attribute' => $this->createAttribute('array', 'field'),
            ),
            'guess' => array(
                'expected' => new TypeGuess('text', array(), TypeGuess::VERY_HIGH_CONFIDENCE),
                'attribute' => $this->createAttribute('string', 'entity.field'),
            )
        );
    }

    /**
     * @param string $type
     * @param string $propertyPath
     * @param array $options
     * @return Attribute
     */
    protected function createAttribute($type, $propertyPath = null, array $options = array())
    {
        $attribute = new Attribute();
        $attribute->setType($type)
            ->setPropertyPath($propertyPath)
            ->setOptions($options);

        return $attribute;
    }

    /**
     * @param array $metadataArray
     */
    protected function setEntityMetadata(array $metadataArray)
    {
        $valueMap = [];
        foreach ($metadataArray as $entity => $metadata) {
            $valueMap[] = [$entity, $metadata];
        }

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($this->isType('string'))
            ->will($this->returnValueMap($valueMap));

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->isType('string'))
            ->will($this->returnValue($entityManager));
    }
}
