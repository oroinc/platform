<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class SearchListener
{
    /**
     * @var OwnershipMetadataProvider
     */
    protected $metadataProvider;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    public function __construct(OwnershipMetadataProvider $metadataProvider, SecurityFacade $securityFacade)
    {
        $this->metadataProvider = $metadataProvider;
        $this->securityFacade = $securityFacade;
    }

    public function prepareEntityMapEvent(PrepareEntityMapEvent $event)
    {
        $data = $event->getData();
        $className = $event->getClassName();
        $entity = $event->getEntity();
        $organizationId = -1;

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
                $organizationId =  $propertyAccessor->getValue($entity, $organizationField)->getId();
            }
        }

        $data['integer']['organization'] = $organizationId;

        $event->setData($data);
    }
}
