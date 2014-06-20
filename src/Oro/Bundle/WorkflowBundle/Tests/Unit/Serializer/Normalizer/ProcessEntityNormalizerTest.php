<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessEntityNormalizer;

class ProcessEntityNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $serializer;

    /**
     * @var ProcessEntityNormalizer
     */
    protected $normalizer;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Serializer\ProcessDataSerializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new ProcessEntityNormalizer($this->registry, $this->doctrineHelper);
        $this->normalizer->setSerializer($this->serializer);
    }

    public function testNormalizeExistingEntity()
    {
        $entity = new \stdClass();
        $entityId = 1;
        $format = 'json';
        $context = array('processJob' => $this->createProcessJob(ProcessTrigger::EVENT_CREATE));

        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->with($entity)
            ->will($this->returnValue($entityId));

        $this->assertEquals(
            array('className' => get_class($entity), 'entityId' => $entityId),
            $this->normalizer->normalize($entity, $format, $context)
        );
    }

    public function testNormalizeDeletedEntity()
    {
        $entity = new \stdClass();
        $entity->first = 1;
        $entity->second = 2;
        $format = 'json';
        $context = array('processJob' => $this->createProcessJob(ProcessTrigger::EVENT_DELETE));

        $this->prepareMetadata(get_class($entity), array('first', 'second'));
        $this->serializer->expects($this->any())->method('normalize')->with($this->isType('int'), $format, $context)
            ->will($this->returnArgument(0));

        $this->assertEquals(
            array('className' => get_class($entity), 'entityData' => array('first' => 1, 'second' => 2)),
            $this->normalizer->normalize($entity, $format, $context)
        );
    }

    public function testDenormalizeExistingEntity()
    {
        $entity = new \stdClass();
        $entityId = 1;
        $className = get_class($entity);

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())->method('find')->with($className, $entityId)
            ->will($this->returnValue($entity));

        $this->registry->expects($this->any())->method('getManagerForClass')->with($className)
            ->will($this->returnValue($entityManager));

        $this->assertEquals(
            $entity,
            $this->normalizer->denormalize(array('className' => $className, 'entityId' => $entityId), null)
        );
    }

    public function testDenormalizeDeletedEntity()
    {
        $entity = new \stdClass();
        $entity->first = 1;
        $entity->second = 2;
        $className = get_class($entity);
        $format = 'json';
        $context = array('processJob' => $this->createProcessJob(ProcessTrigger::EVENT_DELETE));

        $this->prepareMetadata(get_class($entity), array('first', 'second'));
        $this->serializer->expects($this->any())->method('denormalize')
            ->with($this->isType('int'), null, $format, $context)
            ->will($this->returnArgument(0));

        $normalizedData = array('className' => $className, 'entityData' => array('first' => 1, 'second' => 2));
        $this->assertEquals($entity, $this->normalizer->denormalize($normalizedData, null, $format, $context));
    }

    /**
     * @param string $className
     * @param array $fieldNames
     */
    protected function prepareMetadata($className, array $fieldNames)
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->setMethods(array('getFieldNames', 'getFieldValue', 'getReflectionClass', 'getReflectionProperty'))
            ->getMock();
        $metadata->expects($this->any())->method('getFieldNames')
            ->will($this->returnValue($fieldNames));
        $metadata->expects($this->any())->method('getFieldValue')
            ->will(
                $this->returnCallback(
                    function ($entity, $field) {
                        return $entity->$field;
                    }
                )
            );
        $metadata->expects($this->any())->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass($className)));
        $metadata->expects($this->any())->method('getReflectionProperty')->with($this->isType('string'))
            ->will(
                $this->returnCallback(
                    function ($name) use ($className) {
                        $reflection = $this->getMockBuilder('\ReflectionProperty')
                            ->disableOriginalConstructor()
                            ->setMethods(array('setAccessible', 'setValue'))
                            ->getMock();
                        $reflection->expects($this->atLeastOnce())->method('setAccessible')->with(true);
                        $reflection->expects($this->any())->method('setValue')->will(
                            $this->returnCallback(
                                function ($entity, $value) use ($name) {
                                    return $entity->$name = $value;
                                }
                            )
                        );
                        return $reflection;
                    }
                )
            );

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())->method('getClassMetadata')->with($className)
            ->will($this->returnValue($metadata));

        $this->registry->expects($this->any())->method('getManagerForClass')->with($className)
            ->will($this->returnValue($entityManager));
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization($data, $expected)
    {
        if (is_object($data)) {
            $this->doctrineHelper->expects($this->once())->method('isManageableEntity')->with($data)
                ->will($this->returnValue($data instanceof \stdClass));
        } else {
            $this->doctrineHelper->expects($this->never())->method('isManageableEntity');
        }

        $this->assertEquals($expected, $this->normalizer->supportsNormalization($data));
    }

    public function supportsNormalizationDataProvider()
    {
        return array(
            'null'   => array(null, false),
            'scalar' => array('scalar', false),
            'object' => array(new \DateTime(), false),
            'entity' => array(new \stdClass(), true),
        );
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization($data, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization($data, 'json'));
    }

    public function supportsDenormalizationDataProvider()
    {
        return array(
            'null'   => array(null, false),
            'scalar' => array('scalar', false),
            'object' => array(array('serialized_data'), false),
            'entity' => array(array('className' => 'stdClass', 'entityData' => array()), true),
        );
    }

    /**
     * @param string $event
     * @return ProcessJob
     */
    protected function createProcessJob($event)
    {
        $definition = new ProcessDefinition();
        $definition->setRelatedEntity('Test\Entity');

        $trigger = new ProcessTrigger();
        $trigger->setDefinition($definition)
            ->setEvent($event);

        $job = new ProcessJob();
        $job->setProcessTrigger($trigger);

        return $job;
    }
}
