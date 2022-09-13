<?php

namespace Oro\Bundle\EntityConfigBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProviderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
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
        $messageBody = $message->getBody();

        $attributeFamilyRepository = $this->doctrineHelper->getEntityRepositoryForClass(AttributeFamily::class);
        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $attributeFamilyRepository->find($messageBody['attributeFamilyId']);

        $manager = $this->doctrineHelper->getEntityManagerForClass($attributeFamily->getEntityClass());
        $manager->beginTransaction();
        try {
            $this->deletedAttributeProvider->removeAttributeValues(
                $attributeFamily,
                $messageBody['attributeNames']
            );

            $manager->commit();
        } catch (\Exception $e) {
            $manager->rollback();
            $this->logger->error(
                'Unexpected exception occurred during deleting attribute relation',
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
