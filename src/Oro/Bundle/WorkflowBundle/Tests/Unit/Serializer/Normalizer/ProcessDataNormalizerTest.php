<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessDataNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;

class ProcessDataNormalizerTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private Serializer&MockObject $serializer;
    private ProcessDataNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->serializer = $this->createMock(Serializer::class);

        $this->normalizer = new ProcessDataNormalizer($this->doctrineHelper);
        $this->normalizer->setSerializer($this->serializer);
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(ProcessData $object, array $context): void
    {
        $entity = $object['data'];
        $entityId = 1;
        $format = 'json';
        /** @var ProcessJob $processJob */
        $processJob = $context['processJob'];
        $triggerEvent = $processJob->getProcessTrigger()->getEvent();

        $normalizedData = ['serialized', 'data'];

        if (!$entity || $triggerEvent === ProcessTrigger::EVENT_DELETE) {
            $this->doctrineHelper->expects($this->never())
                ->method('getSingleEntityIdentifier');
        } else {
            $this->doctrineHelper->expects($this->once())
                ->method('getSingleEntityIdentifier')
                ->with($entity)
                ->willReturn($entityId);
        }

        $this->serializer->expects($this->once())
            ->method('normalize')
            ->with($object->getValues(), $format, $context)
            ->willReturn($normalizedData);

        $this->assertEquals($normalizedData, $this->normalizer->normalize($object, $format, $context));
        if (!$entity || $triggerEvent === ProcessTrigger::EVENT_DELETE) {
            $this->assertNull($processJob->getEntityId());
        } else {
            $this->assertEquals($entityId, $processJob->getEntityId());
        }
    }

    public function normalizeDataProvider(): array
    {
        return [
            'create' => [
                'object' => new ProcessData(['data' => new \stdClass()]),
                'context' => ['processJob' => $this->createProcessJob(ProcessTrigger::EVENT_CREATE)],
            ],
            'update' => [
                'object' => new ProcessData(['data' => new \stdClass(), 'old' => 1, 'new' => 2]),
                'context' => ['processJob' => $this->createProcessJob(ProcessTrigger::EVENT_UPDATE)],
            ],
            'delete' => [
                'object' => new ProcessData(['data' => new \stdClass()]),
                'context' => ['processJob' => $this->createProcessJob(ProcessTrigger::EVENT_DELETE)],
            ],
            'cron' => [
                'object' => new ProcessData(),
                'context' => ['processJob' => $this->createProcessJob()],
            ],
        ];
    }

    /**
     * @dataProvider normalizeExceptionDataProvider
     */
    public function testNormalizeException(
        ProcessData $object,
        array $context,
        string $exception,
        string $message
    ): void {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);
        $this->normalizer->normalize($object, 'json', $context);
    }

    public function normalizeExceptionDataProvider(): array
    {
        return [
            'no process job' => [
                'object'    => new ProcessData(['data' => new \stdClass()]),
                'context'   => [],
                'exception' => \LogicException::class,
                'message'   => 'Process job is not defined',
            ],
            'invalid process job' => [
                'object'    => new ProcessData(['data' => new \stdClass()]),
                'context'   => ['processJob' => new \stdClass()],
                'exception' => \LogicException::class,
                'message'   => 'Invalid process job entity',
            ],
        ];
    }

    public function testDenormalize(): void
    {
        $data = ['data' => new \stdClass(), 'old' => 1, 'new' => 2];
        $class = ProcessData::class;
        $format = 'json';
        $context = ['processJob' => new ProcessJob()];

        /** @var ProcessData $processData */
        $processData = $this->normalizer->denormalize($data, $class, $format, $context);
        $this->assertInstanceOf($class, $processData);
        $this->assertFalse($processData->isModified());
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(mixed $data, bool $expected): void
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($data));
    }

    public function supportsNormalizationDataProvider(): array
    {
        return [
            'null'        => [null, false],
            'scalar'      => ['scalar', false],
            'datetime'    => [new \DateTime(), false],
            'processData' => [new ProcessData(), true],
            'stdClass'    => [new \stdClass(), false],
        ];
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization(string $type, bool $expected): void
    {
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization([], $type));
    }

    public function supportsDenormalizationDataProvider(): array
    {
        return [
            'null'        => ['', false],
            'string'      => ['string', false],
            'dateTime'    => ['DateTime', false],
            'processData' => [ProcessData::class, true],
            'stdClass'    => ['stdClass', false],
        ];
    }

    private function createProcessJob(?string $event = null): ProcessJob
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
