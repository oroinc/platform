<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SearchListener
{
    const EMPTY_ORGANIZATION_ID = 0;
    const EMPTY_OWNER_ID        = 0;

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
        $this->securityFacade   = $securityFacade;
    }

    /**
     * Add organization field to the entities
     */
    public function collectEntityMapEvent(SearchMappingCollectEvent $event)
    {
        $mapConfig = $event->getMappingConfig();
        foreach ($mapConfig as $className => $mapping) {
            $mapConfig[$className]['fields'][] = [
                'name'          => 'organization',
                'target_type'   => 'integer',
                'target_fields' => ['organization']
            ];

            $metadata = $this->metadataProvider->getMetadata($className);
            if ($metadata
                && in_array(
                    $metadata->getOwnerType(),
                    [OwnershipMetadata::OWNER_TYPE_USER, OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT]
                )
            ) {
                $ownerField                        = sprintf('%s_owner', $mapping['alias']);
                $mapConfig[$className]['fields'][] = [
                    'name'          => $metadata->getOwnerFieldName(),
                    'target_type'   => 'integer',
                    'target_fields' => [$ownerField]
                ];
            }
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
        $data           = $event->getData();
        $className      = $event->getClassName();
        $entity         = $event->getEntity();
        $organizationId = self::EMPTY_ORGANIZATION_ID;
        $ownerId        = self::EMPTY_OWNER_ID;

        $metadata = $this->metadataProvider->getMetadata($className);
        if ($metadata) {
            $organizationField = null;
            if ($metadata->getGlobalOwnerFieldName()) {
                $organizationField = $metadata->getGlobalOwnerFieldName();
            }

            if ($metadata->isGlobalLevelOwned()) {
                $organizationField = $metadata->getOwnerFieldName();
            }

            $propertyAccessor = PropertyAccess::createPropertyAccessor();

            if ($organizationField) {
                /** @var Organization $organization */
                $organization = $propertyAccessor->getValue($entity, $organizationField);
                if ($organization && null !== $organization->getId()) {
                    $organizationId = $organization->getId();
                }
            }

            if (in_array(
                $metadata->getOwnerType(),
                [OwnershipMetadata::OWNER_TYPE_USER, OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT]
            )) {
                $ownerField = sprintf('%s_owner', $event->getEntityMapping()['alias']);

                $owner = $propertyAccessor->getValue($entity, $metadata->getOwnerFieldName());
                if ($owner && $owner->getId()) {
                    $ownerId = $owner->getId();
                }

                $data['integer'][$ownerField] = $ownerId;
            }
        }

        $data['integer']['organization'] = $organizationId;

        $event->setData($data);
    }
}
