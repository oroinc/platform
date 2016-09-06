<?php
namespace Oro\Bundle\SearchBundle\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class Indexer implements IndexerInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @param MessageProducerInterface $producer
     * @param DoctrineHelper           $doctrineHelper
     */
    public function __construct(MessageProducerInterface $producer, DoctrineHelper $doctrineHelper)
    {
        $this->producer = $producer;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, $context = [])
    {
        return $this->doIndex($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, $context = [])
    {
        return $this->doIndex($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, $context = [])
    {
        throw new \LogicException('Method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getClassesForReindex($class = null, $context = [])
    {
        throw new \LogicException('Method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function reindex($class = null, $context = [])
    {
        $classes = is_array($class) ? $class : ($class ? [$class] : []);

        $this->producer->send(Topics::REINDEX, $classes);
    }

    /**
     * @param string|array $entity
     *
     * @return bool
     */
    protected function doIndex($entity)
    {
        if (false == $entity) {
            return false;
        }

        $entities = is_array($entity) ? $entity : [$entity];

        $body = [];
        foreach ($entities as $entity) {
            $body[] = [
                'class' => $this->doctrineHelper->getEntityMetadata($entity)->getName(),
                'id' => $this->doctrineHelper->getSingleEntityIdentifier($entity),
            ];
        }

        $this->producer->send(Topics::INDEX_ENTITIES, $body);

        return true;
    }
}
