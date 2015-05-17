<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SearchListener
{
    const EMPTY_ORGANIZATION_ID = 0;

    /**
     * @var OwnershipMetadataProvider
     */
    protected $metadataProvider;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param OwnershipMetadataProvider $metadataProvider
     * @param SecurityFacade            $securityFacade
     */
    public function __construct(OwnershipMetadataProvider $metadataProvider, SecurityFacade $securityFacade)
    {
        $this->metadataProvider = $metadataProvider;
        $this->securityFacade = $securityFacade;
    }

    /**
     * Add organization field to the entities
     */
    public function collectEntityMapEvent(SearchMappingCollectEvent $event)
    {
        $mapConfig = $event->getMappingConfig();
        foreach (array_keys($mapConfig) as $className) {
            $mapConfig[$className]['fields'][] =
                [
                    'name' => 'organization',
                    'target_type' => 'integer',
                    'target_fields' => ['organization']
                ];
        }

        $event->setMappingConfig($mapConfig);
    }

    /**
     * Add organization field to the search mapping
     *
     * @param PrepareEntityMapEvent $event
     */
    public function prepareEntityMapEvent(PrepareEntityMapEvent $event)
    {
        $data = $event->getData();
        $className = $event->getClassName();
        $entity = $event->getEntity();
        $organizationId = self::EMPTY_ORGANIZATION_ID;

        $metadata = $this->metadataProvider->getMetadata($className);
        if ($metadata) {
            $organizationField = null;
            if ($metadata->getOrganizationFieldName()) {
                $organizationField = $metadata->getOrganizationFieldName();
            }

            if ($metadata->isOrganizationOwned()) {
                $organizationField = $metadata->getOwnerFieldName();
            }

            if ($organizationField) {
                $propertyAccessor = PropertyAccess::createPropertyAccessor();
                /** @var Organization $organization */
                $organization = $propertyAccessor->getValue($entity, $organizationField);
                if ($organization && null !== $organization->getId()) {
                    $organizationId = $organization->getId();
                }
            }
        }

        $data['integer']['organization'] = $organizationId;

        $event->setData($data);
    }

    /**
     * Add Organization limitation for search data
     *
     * @param BeforeSearchEvent $event
     */
    public function beforeSearchEvent(BeforeSearchEvent $event)
    {
        $query = $event->getQuery();
        $organizationId = $this->securityFacade->getOrganizationId();
        if ($organizationId) {
            $query->andWhere('organization', 'in', [$organizationId, self::EMPTY_ORGANIZATION_ID], 'integer');
        }
        $event->setQuery($query);
    }
}
