<?php
namespace Oro\Bundle\SearchBundle\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Client\MessageProducer;

class Indexer implements IndexerInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var MessageProducer
     */
    protected $producer;

    /**
     * @param MessageProducer $producer
     * @param DoctrineHelper  $doctrineHelper
     */
    public function __construct(MessageProducer $producer, DoctrineHelper $doctrineHelper)
    {
        $this->producer = $producer;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity)
    {
        return $this->doIndex($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity)
    {
        return $this->doIndex($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null)
    {
        throw new \LogicException('Method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getClassesForReindex($class = null)
    {
        throw new \LogicException('Method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function reindex($class = null)
    {
        $classes = is_array($class) ? $class : [$class];

        $this->producer->send(Topics::REINDEX_ENTITIES, $classes);
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
                'class' => get_class($entity),
                'id' => $this->doctrineHelper->getEntityIdentifier($entity),
            ];
        }

        $this->producer->send(Topics::INDEX_ENTITIES_BY_ID, $body);

        return true;
    }
}
