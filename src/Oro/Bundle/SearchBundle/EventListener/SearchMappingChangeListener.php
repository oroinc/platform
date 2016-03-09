<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

class SearchMappingChangeListener
{
    /** @var SearchMappingProvider */
    protected $searchMappingProvider;

    /** @var array */
    protected $configClassNames = [
        'Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel',
        'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel',
    ];

    /**
     * @param SearchMappingProvider $searchMappingProvider
     */
    public function __construct(SearchMappingProvider $searchMappingProvider)
    {
        $this->searchMappingProvider = $searchMappingProvider;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();
        if ($this->isConfigChanged($uow)) {
            $this->searchMappingProvider->clearMappingCache();
        }
    }

    /**
     * @param UnitOfWork $uow
     *
     * @return boolean
     */
    public function isConfigChanged(UnitOfWork $uow)
    {
        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions()
        );

        foreach ($entities as $entity) {
            foreach ($this->configClassNames as $className) {
                if ($entity instanceof $className) {
                    return true;
                }
            }
        }

        return false;
    }
}
