<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

/**
 * Normalizes/denormalizes before processing it
 */
class ProcessDataNormalizer extends AbstractProcessNormalizer
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        /** @var ProcessData $object */
        $processJob = $this->getProcessJob($context);
        $entity = $object['data'];

        if (!$entity || $processJob->getProcessTrigger()->getEvent() == ProcessTrigger::EVENT_DELETE) {
            $processJob->setEntityId(null);
        } else {
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            $processJob->setEntityId($entityId);
        }

        return $this->serializer->normalize($object->getValues(), $format, $context);
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        $denormalizedData = is_array($data) ? $data : [];

        return new ProcessData($denormalizedData);
    }

    #[\Override]
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return is_object($data) && $this->supportsClass($this->getClass($data));
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->supportsClass($type);
    }

    /**
     * Checks if the given class is ProcessData or it's ancestor.
     */
    protected function supportsClass(string $class): bool
    {
        return is_a($class, ProcessData::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ProcessData::class => true];
    }
}
