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

    /**
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
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

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $denormalizedData = $this->serializer->denormalize($data, '', $format, $context);
        $denormalizedData = $denormalizedData ?: [];

        return new ProcessData($denormalizedData);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $this->supportsClass($this->getClass($data));
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return $this->supportsClass($type);
    }

    /**
     * Checks if the given class is ProcessData or it's ancestor.
     *
     * @param string $class
     * @return boolean
     */
    protected function supportsClass($class)
    {
        return is_a($class, ProcessData::class, true);
    }
}
