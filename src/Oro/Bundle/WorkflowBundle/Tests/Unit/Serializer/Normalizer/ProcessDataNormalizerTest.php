<?php
namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessDataNormalizer;

class ProcessDataNormalizerTest extends \PHPUnit_Framework_TestCase
{
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
        $this->serializer = $this->getMockForAbstractClass('Symfony\Component\Serializer\SerializerInterface');
        $this->normalizer = new ProcessDataNormalizer();
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
            /** @var $entity \Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition*/
            $entity = $denormalizedValue['entity'];
            $this->serializer->expects($this->at(0))
                ->method('serialize')
                ->with($entity->getCreatedAt(), 'json')
                ->will($this->returnValue($normalizedValue['entity']['createdAt']));
            $this->serializer->expects($this->at(1))
                ->method('serialize')
                ->with($entity->getUpdatedAt(), 'json')
                ->will($this->returnValue($normalizedValue['entity']['updatedAt']));
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
        $normalizedData['className'] = get_class($entity);
        $reflection = new \ReflectionClass($entity);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $name = $property->getName();
            $reflection = new \ReflectionProperty($entity, $name);
            $reflection->setAccessible(true);
            $attribute = $reflection->getValue($entity);
            if ($attribute instanceof \DateTime) {
                $attribute = $attribute->format(\DateTime::ISO8601);
            }
            $normalizedData[$name] = is_object($attribute) ? null : $attribute;
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
            'dateTime'    => array('DateTime', true),
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
            'datetime'    => array(new \DateTime(), true),
            'processData' => array(new ProcessData(), true),
            'stdClass'    => array(new \stdClass(), false),
        );
    }
}
