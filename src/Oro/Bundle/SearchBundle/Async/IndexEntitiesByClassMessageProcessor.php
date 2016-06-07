<?php
namespace Oro\Bundle\SearchBundle\Async;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\OrderBy;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IndexEntitiesByClassMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const BATCH_SIZE = 1000;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var MessageProducer
     */
    protected $producer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param RegistryInterface $doctrine
     * @param MessageProducer   $producer
     * @param LoggerInterface   $logger
     */
    public function __construct(RegistryInterface $doctrine, MessageProducer $producer, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->producer = $producer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $class = $message->getBody();

        /** @var EntityManager $em */
        if (false == $em = $this->doctrine->getManagerForClass($class)) {
            $this->logger->error(sprintf('Entity manager is not defined for class: "%s"', $class));

            return self::REJECT;
        }

        $identifierFieldName = $em->getClassMetadata($class)->getSingleIdentifierFieldName();

        $orderingsExpr = new OrderBy();
        $orderingsExpr->add('entity.' . $identifierFieldName);

        $queryBuilder = $em->getRepository($class)
            ->createQueryBuilder('entity')
            ->select('entity.' . $identifierFieldName)
            ->orderBy($orderingsExpr)
        ;

        $iterator = new BufferedQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(static::BATCH_SIZE);
        $iterator->setHydrationMode(AbstractQuery::HYDRATE_SCALAR);

        $itemsCount = 0;
        foreach ($iterator as $record) {
            $itemsCount++;

            $this->producer->sendTo(Topics::INDEX_ENTITY, [
                'class' => $class,
                'id' => $record[$identifierFieldName],
            ]);

            if (0 == $itemsCount % static::BATCH_SIZE) {
                $em->clear();
            }
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::INDEX_ENTITIES_BY_CLASS];
    }
}
