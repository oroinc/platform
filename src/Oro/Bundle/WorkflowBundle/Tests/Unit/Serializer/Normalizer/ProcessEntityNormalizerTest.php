<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessEntityNormalizer;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer\Stub\Entity;
use Symfony\Component\Serializer\Serializer;

class ProcessEntityNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var Serializer|\PHPUnit\Framework\MockObject\MockObject */
    private $serializer;

    /** @var ProcessEntityNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->serializer = $this->createMock(Serializer::class);

        $this->normalizer = new ProcessEntityNormalizer($this->registry, $this->doctrineHelper);
        $this->normalizer->setSerializer($this->serializer);
    }

    public function testNormalizeExistingEntity(): void
    {
        $entity = new Entity();
        $entityId = 1;
        $format = 'json';
        $context = ['processJob' => $this->createProcessJob(ProcessTrigger::EVENT_CREATE)];

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);

        self::assertEquals(
            ['className' => get_class($entity), 'entityId' => $entityId],
            $this->normalizer->normalize($entity, $format, $context)
        );
    }

    public function testNormalizeDeletedEntity(): void
    {
        $entity = new Entity();
        $entity->first = 1;
        $entity->second = 2;
        $format = 'json';
        $context = ['processJob' => $this->createProcessJob(ProcessTrigger::EVENT_DELETE)];

        $this->prepareMetadata(get_class($entity), ['first', 'second']);
        $this->serializer->expects(self::any())
            ->method('normalize')
            ->with(self::isType('int'), $format, $context)
            ->willReturnArgument(0);

        self::assertEquals(
            ['className' => get_class($entity), 'entityData' => ['first' => 1, 'second' => 2]],
            $this->normalizer->normalize($entity, $format, $context)
        );
    }

    public function testDenormalizeExistingEntity(): void
    {
        $entity = new Entity();
        $entityId = 1;
        $className = get_class($entity);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::once())
            ->method('find')
            ->with($className, $entityId)
            ->willReturn($entity);

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        self::assertEquals(
            $entity,
            $this->normalizer->denormalize(['className' => $className, 'entityId' => $entityId], '')
        );
    }

    public function testDenormalizeDeletedEntity(): void
    {
        $entity = new Entity();
        $entity->first = 1;
        $entity->second = 2;
        $className = get_class($entity);
        $format = 'json';
        $context = ['processJob' => $this->createProcessJob(ProcessTrigger::EVENT_DELETE)];

        $this->prepareMetadata(get_class($entity), ['first', 'second']);
        $this->serializer->expects(self::any())
            ->method('denormalize')
            ->with(self::isType('int'), '', $format, $context)
            ->willReturnArgument(0);

        $normalizedData = ['className' => $className, 'entityData' => ['first' => 1, 'second' => 2]];
        self::assertEquals(
            $entity,
            $this->normalizer->denormalize($normalizedData, '', $format, $context)
        );
    }

    private function prepareMetadata(string $className, array $fieldNames): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::any())
            ->method('getFieldNames')
            ->willReturn($fieldNames);
        $metadata->expects(self::any())
            ->method('getFieldValue')
            ->willReturnCallback(function ($entity, $field) {
                return $entity->{$field};
            });
        $metadata->expects(self::any())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass($className));
        $metadata->expects(self::any())
            ->method('getReflectionProperty')
            ->with(self::isType('string'))
            ->willReturnCallback(function ($name) use ($className) {
                return new \ReflectionProperty($className, $name);
            });

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($metadata);

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(mixed $data, bool $expected): void
    {
        if (is_object($data)) {
            $this->doctrineHelper->expects(self::once())
                ->method('isManageableEntity')
                ->with($data)
                ->willReturn($data instanceof \stdClass);
        } else {
            $this->doctrineHelper->expects(self::never())
                ->method('isManageableEntity');
        }

        self::assertEquals($expected, $this->normalizer->supportsNormalization($data));
    }

    public function supportsNormalizationDataProvider(): array
    {
        return [
            'null' => [null, false],
            'scalar' => ['scalar', false],
            'object' => [new \DateTime(), false],
            'entity' => [new \stdClass(), true],
        ];
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization(mixed $data, bool $expected): void
    {
        self::assertEquals($expected, $this->normalizer->supportsDenormalization($data, ''));
    }

    public function supportsDenormalizationDataProvider(): array
    {
        return [
            'null' => [null, false],
            'scalar' => ['scalar', false],
            'object' => [['serialized_data'], false],
            'entity' => [['className' => 'stdClass', 'entityData' => []], true],
        ];
    }

    private function createProcessJob(string $event): ProcessJob
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
