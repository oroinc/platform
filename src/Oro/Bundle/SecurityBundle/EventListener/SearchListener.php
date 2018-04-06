<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class SearchListener
{
    const EMPTY_ORGANIZATION_ID = 0;
    const EMPTY_OWNER_ID        = 0;

    /** @var OwnershipMetadataProviderInterface */
    protected $metadataProvider;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param OwnershipMetadataProviderInterface $metadataProvider
     */
    public function __construct(OwnershipMetadataProviderInterface $metadataProvider)
    {
        $this->metadataProvider = $metadataProvider;
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
            $metadata = $this->metadataProvider->getMetadata($className);
            $mapConfig[$className]['fields'][] = [
                'name'          => 'organization',
                'target_type'   => 'integer',
                'target_fields' => ['organization']
            ];
            $mapConfig[$className]['fields'][] = [
                'name'          => $metadata->getOwnerFieldName(),
                'target_type'   => 'integer',
                'target_fields' => [$this->getOwnerKey($metadata, $mapping['alias'])]
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

        $data['integer'][$this->getOwnerKey($metadata, $event->getEntityMapping()['alias'])] = $ownerId;
        $data['integer']['organization'] = $organizationId;

        $event->setData($data);
    }

    /**
     * Get entity owner id
     *
     * @param OwnershipMetadataInterface $metadata
     * @param object $entity
     *
     * @return int
     */
    protected function getOwnerId(OwnershipMetadataInterface $metadata, $entity)
    {
        $ownerId = self::EMPTY_OWNER_ID;

        if ($metadata->isUserOwned() || $metadata->isBusinessUnitOwned() || $metadata->isOrganizationOwned()) {
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
     * @param OwnershipMetadataInterface $metadata
     * @param object $entity
     *
     * @return int
     */
    protected function getOrganizationId(OwnershipMetadataInterface $metadata, $entity)
    {
        $organizationId = self::EMPTY_ORGANIZATION_ID;

        $organizationField = $this->getOrganizationField($metadata);

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
     * @param OwnershipMetadataInterface $metadata
     * @param string $entityAlias
     * @return string
     */
    protected function getOwnerKey(OwnershipMetadataInterface $metadata, $entityAlias)
    {
        return sprintf('%s_%s', $entityAlias, $metadata->getOwnerFieldName());
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

    /**
     * @param OwnershipMetadataInterface $metadata
     *
     * @return null|string
     */
    protected function getOrganizationField(OwnershipMetadataInterface $metadata)
    {
        $organizationField = null;

        if ($metadata->getOrganizationFieldName()) {
            $organizationField = $metadata->getOrganizationFieldName();
        }

        if ($metadata->isOrganizationOwned()) {
            $organizationField = $metadata->getOwnerFieldName();
        }

        return $organizationField;
    }
}
