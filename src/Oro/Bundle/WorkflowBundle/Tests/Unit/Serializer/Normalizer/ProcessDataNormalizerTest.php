<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessDataNormalizer;

class ProcessDataNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $serializer;

    /**
     * @var ProcessDataNormalizer
     */
    protected $normalizer;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Serializer\ProcessDataSerializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new ProcessDataNormalizer($this->doctrineHelper);
        $this->normalizer->setSerializer($this->serializer);
    }

    /**
     * @param ProcessData $object
     * @param array $context
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize($object, array $context)
    {
        $entity = $object['data'];
        $entityId = 1;
        $format = 'json';
        /** @var ProcessJob $processJob */
        $processJob = $context['processJob'];
        $triggerEvent = $processJob->getProcessTrigger()->getEvent();

        $normalizedData = array('serialized', 'data');

        if (!$entity || $triggerEvent == ProcessTrigger::EVENT_DELETE) {
            $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifier');
        } else {
            $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->with($entity)
                ->will($this->returnValue($entityId));
        }

        $this->serializer->expects($this->once())->method('normalize')
            ->with($object->getValues(), $format, $context)->will($this->returnValue($normalizedData));

        $this->assertEquals($normalizedData, $this->normalizer->normalize($object, $format, $context));
        if (!$entity || $triggerEvent == ProcessTrigger::EVENT_DELETE) {
            $this->assertNull($processJob->getEntityId());
        } else {
            $this->assertEquals($entityId, $processJob->getEntityId());
        }
    }

    /**
     * @return array
     */
    public function normalizeDataProvider()
    {
        return array(
            'create' => array(
                'object' => new ProcessData(array('data' => new \stdClass())),
                'context' => array('processJob' => $this->createProcessJob(ProcessTrigger::EVENT_CREATE)),
            ),
            'update' => array(
                'object' => new ProcessData(array('data' => new \stdClass(), 'old' => 1, 'new' => 2)),
                'context' => array('processJob' => $this->createProcessJob(ProcessTrigger::EVENT_UPDATE)),
            ),
            'delete' => array(
                'object' => new ProcessData(array('data' => new \stdClass())),
                'context' => array('processJob' => $this->createProcessJob(ProcessTrigger::EVENT_DELETE)),
            ),
            'cron' => array(
                'object' => new ProcessData(),
                'context' => array('processJob' => $this->createProcessJob()),
            ),
        );
    }

    /**
     * @param ProcessData $object
     * @param array $context
     * @param string $exception
     * @param string $message
     * @dataProvider normalizeExceptionDataProvider
     */
    public function testNormalizeException($object, array $context, $exception, $message)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);
        $this->normalizer->normalize($object, 'json', $context);
    }

    /**
     * @return array
     */
    public function normalizeExceptionDataProvider()
    {
        return array(
            'no process job' => array(
                'object'    => new ProcessData(array('data' => new \stdClass())),
                'context'   => array(),
                'exception' => '\LogicException',
                'message'   => 'Process job is not defined',
            ),
            'invalid process job' => array(
                'object'    => new ProcessData(array('data' => new \stdClass())),
                'context'   => array('processJob' => new \stdClass()),
                'exception' => '\LogicException',
                'message'   => 'Invalid process job entity',
            ),
        );
    }

    public function testDenormalize()
    {
        $data = array('data' => new \stdClass(), 'old' => 1, 'new' => 2);
        $class = 'Oro\Bundle\WorkflowBundle\Model\ProcessData';
        $format = 'json';
        $context = array('processJob' => new ProcessJob());
        $denormalizedData = array('denormalized', 'data');

        $this->serializer->expects($this->once())->method('denormalize')->with($data, null, $format, $context)
            ->will($this->returnValue($denormalizedData));

        /** @var ProcessData $processData */
        $processData = $this->normalizer->denormalize($data, $class, $format, $context);
        $this->assertInstanceOf($class, $processData);
        $this->assertFalse($processData->isModified());
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization($data, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($data));
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

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization($type, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization(array(), $type));
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
     * @param string $event
     * @return ProcessJob
     */
    protected function createProcessJob($event = null)
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
