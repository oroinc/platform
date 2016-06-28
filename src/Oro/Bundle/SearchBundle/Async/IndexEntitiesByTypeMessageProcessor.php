<?php
namespace Oro\Bundle\SearchBundle\Async;

use Doctrine\ORM\EntityManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IndexEntitiesByTypeMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const BATCH_SIZE = 1000;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param RegistryInterface        $doctrine
     * @param MessageProducerInterface $producer
     * @param LoggerInterface          $logger
     */
    public function __construct(
        RegistryInterface $doctrine,
        MessageProducerInterface $producer,
        LoggerInterface $logger
    ) {
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

        $entityCount = $em->getRepository($class)
            ->createQueryBuilder('entity')
            ->select('COUNT(entity)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $batches = (int) ceil($entityCount / self::BATCH_SIZE);
        for ($i = 0; $i < $batches; $i++) {
            $this->producer->send(Topics::INDEX_ENTITY_BY_RANGE, [
                'class' => $class,
                'offset' => $i * self::BATCH_SIZE,
                'limit' => self::BATCH_SIZE,
            ]);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::INDEX_ENTITY_TYPE];
    }
}
