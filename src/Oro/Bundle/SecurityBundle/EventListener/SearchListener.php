<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SearchListener
{
    const EMPTY_ORGANIZATION_ID = 0;
    const EMPTY_OWNER_ID        = 0;

    /** @var OwnershipMetadataProvider */
    protected $metadataProvider;

    /**  @var SecurityFacade */
    protected $securityFacade;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

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
     *
     * @param SearchMappingCollectEvent $event
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
            $mapConfig[$className]['fields'][] = [
                'name'          => 'owner',
                'target_type'   => 'integer',
                'target_fields' => [sprintf('%s_owner', $mapping['alias'])]
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
        $data      = $event->getData();
        $className = $event->getClassName();

        $organizationId = self::EMPTY_ORGANIZATION_ID;
        $ownerId        = self::EMPTY_OWNER_ID;

        $metadata = $this->metadataProvider->getMetadata($className);
        if ($metadata) {
            $entity         = $event->getEntity();
            $ownerId        = $this->getOwnerId($metadata, $entity);
            $organizationId = $this->getOrganizationId($metadata, $entity);
        }

        $data['integer'][sprintf('%s_owner', $event->getEntityMapping()['alias'])] = $ownerId;
        $data['integer']['organization']                                           = $organizationId;

        $event->setData($data);
    }

    /**
     * Get entity owner id
     *
     * @param OwnershipMetadata $metadata
     * @param object            $entity
     *
     * @return int
     */
    protected function getOwnerId(OwnershipMetadata $metadata, $entity)
    {
        $ownerId = self::EMPTY_OWNER_ID;

        if (in_array(
            $metadata->getOwnerType(),
            [OwnershipMetadata::OWNER_TYPE_USER, OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT]
        )) {
            $owner = $this->getPropertyAccessor()->getValue($entity, $metadata->getOwnerFieldName());
            if ($owner && $owner->getId()) {
                $ownerId = $owner->getId();
            }
        }

        return $ownerId;
    }

    /**
     * Get entity organization id
     *
     * @param OwnershipMetadata $metadata
     * @param object            $entity
     *
     * @return int
     */
    protected function getOrganizationId(OwnershipMetadata $metadata, $entity)
    {
        $organizationId = self::EMPTY_ORGANIZATION_ID;

        $organizationField = null;
        if ($metadata->getGlobalOwnerFieldName()) {
            $organizationField = $metadata->getGlobalOwnerFieldName();
        }

        if ($metadata->isGlobalLevelOwned()) {
            $organizationField = $metadata->getOwnerFieldName();
        }

        if ($organizationField) {
            /** @var Organization $organization */
            $organization = $this->getPropertyAccessor()->getValue($entity, $organizationField);
            if ($organization && null !== $organization->getId()) {
                $organizationId = $organization->getId();
            }
        }

        return $organizationId;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
