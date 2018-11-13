<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Populates imported entities with organization if entity supports it.
 */
class ImportStrategyListener implements ImportStrategyListenerInterface
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
     * {@inheritdoc}
     */
    public function onProcessAfter(StrategyEvent $event)
    {
        $entity = $event->getEntity();

        $organizationField = $this->getOrganizationField($entity);
        if (!$organizationField) {
            return;
        }

        $entityOrganization = $this->getPropertyAccessor()->getValue($entity, $organizationField);
        $tokenOrganization = $this->tokenAccessor->getOrganization();

        if ($entityOrganization) {
            /**
             * Do nothing in case if entity already have organization field value but this value was absent in item data
             * (the value of organization field was set to the entity before the import).
             */
            $data = $event->getContext()->getValue('itemData');
            if ($data && !array_key_exists($organizationField, $data)) {
                return;
            }

            /**
             * We should allow to set organization for entity only in anonymous mode then the token has no organization
             * (for example, console import).
             * If import process was executed not in anonymous mode (for example, grid's import),
             * current organization for entities should be set.
             */
            if (!$tokenOrganization
                || ($tokenOrganization && $entityOrganization->getId() == $this->tokenAccessor->getOrganizationId())
            ) {
                return;
            }
        }

        // By default, the token organization should be set as entity organization.
        $entityOrganization = $tokenOrganization;

        if (!$entityOrganization) {
            $entityOrganization = $this->getDefaultOrganization();
        }

        if (!$entityOrganization) {
            return;
        }

        $this->getPropertyAccessor()->setValue($entity, $organizationField, $entityOrganization);
    }

    /**
     * {@inheritdoc}
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
