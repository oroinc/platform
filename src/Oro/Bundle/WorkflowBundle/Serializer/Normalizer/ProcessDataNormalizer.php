<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ProcessDataNormalizer extends AbstractProcessNormalizer
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $denormalizedData = $this->serializer->denormalize($data, null, $format, $context);
        $denormalizedData = $denormalizedData ?: array();

        return new ProcessData($denormalizedData);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        /** @var ProcessData $object */
        $processJob = $this->getProcessJob($context);
        $entity = $object['entity'];
        if (!$entity) {
            throw new \LogicException('Process entity is not specified');
        }

        if ($processJob->getProcessTrigger()->getEvent() == ProcessTrigger::EVENT_DELETE) {
            $processJob->setEntityId(null);
        } else {
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            $processJob->setEntityId($entityId);
        }

        return $this->serializer->normalize($object->getValues(), $format, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->supportsClass($type);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $this->supportsClass($this->getClass($data));
    }

    /**
     * Checks if the given class is ProcessData or it's ancestor.
     *
     * @param string $class
     * @return boolean
     */
    protected function supportsClass($class)
    {
        $processDataClass = 'Oro\Bundle\WorkflowBundle\Model\ProcessData';

        return $processDataClass == $class ||
               is_string($class) && class_exists($class) && in_array($processDataClass, class_parents($class));
    }
}
