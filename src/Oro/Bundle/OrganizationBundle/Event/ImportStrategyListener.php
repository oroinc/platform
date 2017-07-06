<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class ImportStrategyListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var ServiceLink */
    protected $metadataProviderLink;

    /** @var Organization */
    protected $defaultOrganization;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var array */
    protected $organizationFieldByEntity = [];

    /**
     * @param ManagerRegistry        $registry
     * @param TokenAccessorInterface $tokenAccessor
     * @param ServiceLink            $metadataProviderLink
     */
    public function __construct(
        ManagerRegistry $registry,
        TokenAccessorInterface $tokenAccessor,
        ServiceLink $metadataProviderLink
    ) {
        $this->registry = $registry;
        $this->tokenAccessor = $tokenAccessor;
        $this->metadataProviderLink = $metadataProviderLink;
    }

    /**
     * @param StrategyEvent $event
     */
    public function onProcessAfter(StrategyEvent $event)
    {
        $entity = $event->getEntity();

        $organizationField = $this->getOrganizationField($entity);
        if (!$organizationField) {
            return;
        }

        /**
         * We should allow to set organization for entity only in case of console import.
         * If import process was executed from UI (grid's import), current organization for entities should be set.
         */
        $organization = $this->getPropertyAccessor()->getValue($entity, $organizationField);
        if ($organization
            && $this->tokenAccessor->getOrganization()
            && $organization->getId() == $this->tokenAccessor->getOrganizationId()
        ) {
            return;
        }

        $organization = $this->tokenAccessor->getOrganization();

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
        $this->organizationFieldByEntity = [];
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

    /**
     * @param object $entity
     * @return string
     */
    protected function getOrganizationField($entity)
    {
        $entityName = ClassUtils::getClass($entity);
        if (!array_key_exists($entityName, $this->organizationFieldByEntity)) {
            /** @var OwnershipMetadataProviderInterface $metadataProvider */
            $metadataProvider = $this->metadataProviderLink->getService();
            $this->organizationFieldByEntity[$entityName] = $metadataProvider->getMetadata($entityName)
                ->getOrganizationFieldName();
        }

        return $this->organizationFieldByEntity[$entityName];
    }
}
