<?php
namespace Oro\Bundle\SearchBundle\Async;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IndexEntitiesByRangeMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var IndexerInterface
     */
    protected $indexer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param RegistryInterface $doctrine
     * @param IndexerInterface  $indexer
     * @param LoggerInterface   $logger
     */
    public function __construct(RegistryInterface $doctrine, IndexerInterface $indexer, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->indexer = $indexer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        var_dump($message->getBody());

        $data = JSON::decode($message->getBody());

        if (false == isset($data['class']) || false == isset($data['offset']) || false == isset($data['limit'])) {
            $this->logger->error(sprintf('Message is not valid: "%s"', $message->getBody()));

            return self::REJECT;
        }

        /** @var EntityManager $em */
        if (false == $em = $this->doctrine->getManagerForClass($data['class'])) {
            $this->logger->error(sprintf('Entity manager is not defined for class: "%s"', $data['class']));

            return self::REJECT;
        }

        $identifierFieldName = $em->getClassMetadata($data['class'])->getSingleIdentifierFieldName();
        $repository = $em->getRepository($data['class']);

        $ids = $repository->createQueryBuilder('ids')
            ->select('ids.'.$identifierFieldName)
            ->setFirstResult($data['offset'])
            ->setMaxResults($data['limit'])
            ->orderBy('ids.'.$identifierFieldName, 'ASC')
            ->getQuery()->getArrayResult()
        ;
        $ids = array_map('current', $ids);

        if (false == $ids) {
            return self::ACK;
        }

        $entities = $repository->createQueryBuilder('entity')
            ->where('entity IN(:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()->getResult()
        ;

        $this->indexer->save($entities);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::INDEX_ENTITY_BY_RANGE];
    }
}
