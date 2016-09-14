<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class AsyncIndexer implements IndexerInterface
{
    const TOPIC_SAVE = 'oro.website.search.indexer.save';
    const TOPIC_DELETE = 'oro.website.search.indexer.delete';
    const TOPIC_RESET_INDEX = 'oro.website.search.indexer.reset_index';
    const TOPIC_REINDEX = 'oro.website.search.indexer.reindex';

    /**
     * @var IndexerInterface
     */
    private $baseIndexer;

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @param IndexerInterface $baseIndexer
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(IndexerInterface $baseIndexer, MessageProducerInterface $messageProducer)
    {
        $this->baseIndexer = $baseIndexer;
        $this->messageProducer = $messageProducer;
    }

    /**
     * @inheritdoc
     */
    public function save($entity, $context = [])
    {
        $this->sendAsyncIndexerMessage(
            self::TOPIC_SAVE,
            [
                'entity' => $this->getOneOrManyEntitiesScalarRepresentation($entity),
                'context' => $context
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function delete($entity, $context = [])
    {
        $this->sendAsyncIndexerMessage(
            self::TOPIC_DELETE,
            [
                'entity' => $this->getOneOrManyEntitiesScalarRepresentation($entity),
                'context' => $context
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getClassesForReindex($class = null, $context = [])
    {
        return $this->baseIndexer->getClassesForReindex($class, $context);
    }

    /**
     * @inheritdoc
     */
    public function resetIndex($class = null, $context = [])
    {
        $this->sendAsyncIndexerMessage(
            self::TOPIC_RESET_INDEX,
            [
                'class' => $class,
                'context' => $context
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function reindex($class = null, $context = [])
    {
        $this->sendAsyncIndexerMessage(
            self::TOPIC_REINDEX,
            [
                'class' => $class,
                'context' => $context
            ]
        );
    }

    /**
     * Send a message to a que using message producer
     *
     * @param $topic
     * @param array $data
     */
    private function sendAsyncIndexerMessage($topic, array $data)
    {
        $this->messageProducer->send(
            $topic,
            $data
        );
    }

    /**
     * @param object|object[] $entity
     * @return array
     */
    private function getOneOrManyEntitiesScalarRepresentation($entity)
    {
        if (is_array($entity)) {
            $result = [];

            foreach ($entity as $entityEntry)  {
                $result[] = $this->getEntityScalarRepresentation($entityEntry);
            }

            return $result;
        }

        return $this->getEntityScalarRepresentation($entity);
    }

    /**
     * Parse entity and get the Id and class name from it, to send in the que message.
     *
     * @param object $entity
     * @return array
     * @throws \RuntimeException
     */
    private function getEntityScalarRepresentation($entity)
    {
        if (is_object($entity) && method_exists($entity, 'getId')) {
            return [
                'class' => get_class($entity),
                'id' => $entity->getId()
            ];
        }

        throw new \RuntimeException('Id can not be found in the given entity.');
    }
}
