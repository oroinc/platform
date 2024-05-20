<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Async\Topic\CreateOpenApiSpecificationTopic;
use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\FeatureToggleBundle\Event\FeaturesChange;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * The event listener that is used to renew OpenAPI specifications.
 */
class OpenApiSourceListener
{
    use OpenApiSourceListenerTrait;

    private ManagerRegistry $doctrine;
    private MessageProducerInterface $producer;

    /**
     * @param ManagerRegistry          $doctrine
     * @param MessageProducerInterface $producer
     * @param string[]                 $excludedFeatures
     */
    public function __construct(
        ManagerRegistry $doctrine,
        MessageProducerInterface $producer,
        array $excludedFeatures
    ) {
        $this->doctrine = $doctrine;
        $this->producer = $producer;
        $this->excludedFeatures = $excludedFeatures;
    }

    public function renewOpenApiSpecifications(): void
    {
        $items = $this->getOpenApiSpecificationsToRenew();
        if ($items) {
            $this->updateOpenApiSpecificationStatuses();
            foreach ($items as $item) {
                $this->producer->send(
                    CreateOpenApiSpecificationTopic::getName(),
                    ['entityId' => $item['id'], 'renew' => true]
                );
            }
        }
    }

    public function onFeaturesChange(FeaturesChange $event): void
    {
        if ($this->isApplicableFeaturesChanged($event)) {
            $this->renewOpenApiSpecifications();
        }
    }

    public function onEntityConfigPostFlush(PostFlushConfigEvent $event): void
    {
        if ($this->isApplicableEntityConfigsChanged($event)) {
            $this->renewOpenApiSpecifications();
        }
    }

    private function getOpenApiSpecificationsToRenew(): array
    {
        /** @var EntityRepository $repository */
        $repository = $this->doctrine->getRepository(OpenApiSpecification::class);

        return $repository->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.status NOT IN(:renew_status, :creation_status)')
            ->setParameter('renew_status', OpenApiSpecification::STATUS_RENEWING)
            ->setParameter('creation_status', OpenApiSpecification::STATUS_CREATING)
            ->orderBy('e.updatedAt', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    private function updateOpenApiSpecificationStatuses(): void
    {
        /** @var EntityRepository $repository */
        $repository = $this->doctrine->getRepository(OpenApiSpecification::class);
        $repository->createQueryBuilder('e')
            ->update(OpenApiSpecification::class, 'e')
            ->set('e.status', ':renew_status')
            ->where('e.status <> :renew_status AND e.status <> :creation_status')
            ->setParameter('renew_status', OpenApiSpecification::STATUS_RENEWING)
            ->setParameter('creation_status', OpenApiSpecification::STATUS_CREATING)
            ->getQuery()
            ->execute();
    }
}
