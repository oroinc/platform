<?php

namespace Oro\Bundle\EntityConfigBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProviderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Deletes attribute relations
 */
class DeletedAttributeRelationProcessor implements MessageProcessorInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DeletedAttributeProviderInterface
     */
    protected $deletedAttributeProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param LoggerInterface $logger
     * @param DeletedAttributeProviderInterface $deletedAttributeProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        LoggerInterface $logger,
        DeletedAttributeProviderInterface $deletedAttributeProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
        $this->deletedAttributeProvider = $deletedAttributeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageData = JSON::decode($message->getBody());
        if (!isset($messageData['attributeFamilyId'])) {
            $this->logger->critical('Invalid message: key "attributeFamilyId" is missing.');

            return self::REJECT;
        }

        $attributeFamilyRepository = $this->doctrineHelper->getEntityRepositoryForClass(AttributeFamily::class);
        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $attributeFamilyRepository->find($messageData['attributeFamilyId']);

        $manager = $this->doctrineHelper->getEntityManagerForClass($attributeFamily->getEntityClass());
        $manager->beginTransaction();
        try {
            $this->deletedAttributeProvider->removeAttributeValues(
                $attributeFamily,
                $messageData['attributeNames']
            );

            $manager->commit();
        } catch (\Exception $e) {
            $manager->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Deleting attribute relation',
                ['exception' => $e]
            );

            if ($e instanceof RetryableException) {
                return self::REQUEUE;
            }

            return self::REJECT;
        }

        return self::ACK;
    }
}
