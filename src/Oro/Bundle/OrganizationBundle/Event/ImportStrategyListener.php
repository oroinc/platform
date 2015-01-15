<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class ImportStrategyListener
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ServiceLink
     */
    protected $securityFacadeLink;

    /**
     * @var ServiceLink
     */
    protected $metadataProviderLink;

    /**
     * @var Organization
     */
    protected $defaultOrganization;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param ManagerRegistry $registry
     * @param ServiceLink $securityFacadeLink
     * @param ServiceLink $metadataProviderLink
     */
    public function __construct(
        ManagerRegistry $registry,
        ServiceLink $securityFacadeLink,
        ServiceLink $metadataProviderLink
    ) {
        $this->registry = $registry;
        $this->securityFacadeLink = $securityFacadeLink;
        $this->metadataProviderLink = $metadataProviderLink;
    }

    /**
     * @param StrategyEvent $event
     */
    public function onProcessAfter(StrategyEvent $event)
    {
        $entity = $event->getEntity();

        /** @var OwnershipMetadataProvider $metadataProvider */
        $metadataProvider = $this->metadataProviderLink->getService();

        $organizationField = $metadataProvider->getMetadata(ClassUtils::getClass($entity))->getOrganizationFieldName();
        if (!$organizationField) {
            return;
        }

        /** @var SecurityFacade $securityFacade */
        $securityFacade = $this->securityFacadeLink->getService();

        // we should allow to set organization for entity only in case of console import.
        // if import process was run from UI (import from the grid), should be set current organization for entities.
        $organization = $this->getPropertyAccessor()->getValue($entity, $organizationField);
        if ($organization && $securityFacade->getOrganization()) {
            return;
        }

        $organization = $securityFacade->getOrganization();

        if (!$organization) {
            $organization = $this->getDefaultOrganization();
        }

        if (!$organization) {
            return;
        }

        $this->getPropertyAccessor()->setValue($entity, $organizationField, $organization);
    }

    /**
     * Clear default organization on doctrine entity manager clear
     */
    public function onClear()
    {
        $this->defaultOrganization = null;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @return Organization|null
     */
    protected function getDefaultOrganization()
    {
        if (null === $this->defaultOrganization) {
            /** @var EntityRepository $entityRepository */
            $entityRepository = $this->registry->getRepository('OroOrganizationBundle:Organization');
            $organizations = $entityRepository->createQueryBuilder('e')
                ->setMaxResults(2)
                ->getQuery()
                ->getResult();
            if (count($organizations) == 1) {
                $this->defaultOrganization = current($organizations);
            } else {
                $this->defaultOrganization = false;
            }
        }

        return $this->defaultOrganization ?: null;
    }
}
