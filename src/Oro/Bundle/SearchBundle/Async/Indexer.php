<?php

namespace Oro\Bundle\SearchBundle\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Transformer\MessageTransformerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class Indexer implements IndexerInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var MessageTransformerInterface
     */
    private $transformer;

    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @param MessageProducerInterface $producer
     * @param DoctrineHelper $doctrineHelper
     * @param MessageTransformerInterface $transformer
     */
    public function __construct(
        MessageProducerInterface $producer,
        DoctrineHelper $doctrineHelper,
        MessageTransformerInterface $transformer
    ) {
        $this->producer = $producer;
        $this->doctrineHelper = $doctrineHelper;
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, array $context = [])
    {
        return $this->doIndex($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, array $context = [])
    {
        return $this->doIndex($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, array $context = [])
    {
        throw new \LogicException('Method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getClassesForReindex($class = null, array $context = [])
    {
        throw new \LogicException('Method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function reindex($class = null, array $context = [])
    {
        if (is_array($class)) {
            $classes = $class;
        } else {
            $classes = $class ? [$class] : [];
        }

        //Ensure specified class exists, if not - exception will be thrown
        foreach ($classes as $class) {
            $this->doctrineHelper->getEntityManagerForClass($class);
        }

        $this->producer->send(Topics::REINDEX, $classes);
    }

    /**
     * @param string|array $entity
     *
     * @return bool
     */
    protected function doIndex($entity)
    {
        if (!$entity) {
            return false;
        }

        $entities = is_array($entity) ? $entity : [$entity];

        $messages = $this->transformer->transform($entities);
        foreach ($messages as $message) {
            $this->producer->send(Topics::INDEX_ENTITIES, $message);
        }

        return true;
    }
}
