<?php
namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessDataNormalizer;

class ProcessDataNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ProcessDataNormalizer
     */
    protected $normalizer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $serializer;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockForAbstractClass('Symfony\Component\Serializer\SerializerInterface');
        $this->normalizer = new ProcessDataNormalizer($this->registry);
    }

    /**
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize($normalizedData, $denormalizedData)
    {
        $this->normalizer->setSerializer($this->serializer);

        $this->assertEquals(
            $denormalizedData,
            $this->normalizer->denormalize($normalizedData, 'Oro\Bundle\WorkflowBundle\Model\ProcessData')
        );
    }

    public function denormalizeDataProvider()
    {
        return array(
            'simple' => array(
                'normalizedData'   => array('test_attribute' => 'value'),
                'denormalizedData' => new ProcessData(array('test_attribute' => 'value'))
            )
        );
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize($denormalizedValue, $normalizedValue)
    {
        $this->normalizer->setSerializer($this->serializer);

        if (!empty($denormalizedValue['entity'])) {
            $this->assetReflectionMock($denormalizedValue['entity']);
        }

        $this->assertEquals($normalizedValue, $this->normalizer->normalize($denormalizedValue, 'json'));
    }

    public function normalizeDataProvider()
    {

        $simple     = array('test_attribute' => 'value');
        $complexity = array(
            'new' => array(
                'new_attribute1' => 'value1',
                'new_attribute2' => 'value2'
            ),
            'old' => array(
                'old_attribute1' => 'value1',
                'old_attribute2' => 'value2'
            )
        );

        $entity = $this->createEntity();
        $withEntity = array(
            'entity' => $entity,
            'new' => array(
                'new_attribute1' => 'value1',
                'new_attribute2' => 'value2'
            ),
            'old' => array(
                'old_attribute1' => 'value1',
                'old_attribute2' => 'value2'
            )
        );

        $withEntityNormalized = array(
            'entity' => $this->normalizeEntity($entity),
            'new' => array(
                'new_attribute1' => 'value1',
                'new_attribute2' => 'value2'
            ),
            'old' => array(
                'old_attribute1' => 'value1',
                'old_attribute2' => 'value2'
            )
        );
        return array(
            'simple' => array(
                'denormalizedData' => new ProcessData($simple),
                'normalizedData'   => $simple,
            ),
            'more complexity' => array(
                'denormalizedData' => new ProcessData($complexity),
                'normalizedData'   => $complexity,
            ),
            'with entity' => array(
                'denormalizedData' => new ProcessData($withEntity),
                'normalizedData'   => $withEntityNormalized,
            )
        );
    }

    protected function createEntity()
    {
        $attributes = array(
            'createdAt'           => new \DateTime('yesterday'),
            'configuration'       => array('first', 'second', 'third'),
            'entityAcls'          => new ArrayCollection(array('acl1', 'acl2', 'acl3')),
            'entityAttributeName' => 'testEntityAttributeName',
            'label'               => 'testStepLabel',
            'name'                => 'testStepName',
            'relatedEntity'       => 'Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger',
            'startStep'           => $this->getMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep'),
            'steps'               => new ArrayCollection(array('step1', 'step2', 'step3')),
            'stepsDisplayOrdered' => false,
            'system'              => true,
            'updatedAt'           => new \DateTime('now'),
        );
        $className  = 'Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition';
        $reflection = new \ReflectionClass($className);
        $entity     = $reflection->newInstanceWithoutConstructor();

        foreach ($attributes as $name => $value) {
            $reflectionProperty = new \ReflectionProperty($entity, $name);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($entity, $value);
        }

        return $entity;
    }

    protected function normalizeEntity($entity)
    {
        $normalizedData = array(
            'className' => ClassUtils::getClass($entity),
            'classData' => array()
        );
        $reflection = new \ReflectionClass($entity);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $name = $property->getName();
            $reflection = new \ReflectionProperty($entity, $name);
            $reflection->setAccessible(true);
            $attribute = $reflection->getValue($entity);
            if ($attribute instanceof \DateTime) {
                $attribute = base64_encode(serialize($attribute));
            }
            $normalizedData['classData'][$name] = is_object($attribute) ? null : $attribute;
        }

        return $normalizedData;
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization($type, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization('any_value', $type));
    }

    public function supportsDenormalizationDataProvider()
    {
        return array(
            'null'        => array(null, false),
            'string'      => array('string', false),
            'dateTime'    => array('DateTime', false),
            'processData' => array('Oro\Bundle\WorkflowBundle\Model\ProcessData', true),
            'stdClass'    => array('stdClass', false),
        );
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization($data, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($data, 'anyValue'));
    }

    public function supportsNormalizationDataProvider()
    {
        return array(
            'null'        => array(null, false),
            'scalar'      => array('scalar', false),
            'datetime'    => array(new \DateTime(), false),
            'processData' => array(new ProcessData(), true),
            'stdClass'    => array(new \stdClass(), false),
        );
    }

    protected function assetReflectionMock($class)
    {
        $reflection = new \ReflectionClass($class);
        $properties = $reflection->getProperties();

        $classMetadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects($this->once())
            ->method('getReflectionProperties')
            ->will($this->returnValue($properties));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(ClassUtils::getClass($class))
            ->will($this->returnValue($classMetadata));

        $this->registry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));
    }
}
