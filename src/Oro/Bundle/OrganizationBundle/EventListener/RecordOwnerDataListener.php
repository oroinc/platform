<?php

namespace Oro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Listener that sets owner and organization to new entity if this data was not set.
 */
class RecordOwnerDataListener
{
    /** @var TokenAccessor */
    protected $tokenAccessor;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param TokenAccessor  $tokenAccessor
     * @param ConfigProvider $configProvider
     */
    public function __construct(TokenAccessor $tokenAccessor, ConfigProvider $configProvider)
    {
        $this->tokenAccessor = $tokenAccessor;
        $this->configProvider = $configProvider;
    }

    /**
     * Handle prePersist.
     *
     * @param LifecycleEventArgs $args
     *
     * @throws \LogicException when getOwner method isn't implemented for entity with ownership type
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        if (!$this->tokenAccessor->hasUser()) {
            return;
        }

        $entity = $args->getEntity();
        if ($entity instanceof BusinessUnit) {
            return;
        }

        $className = ClassUtils::getClass($entity);
        if ($this->configProvider->hasConfig($className)) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $config = $this->configProvider->getConfig($className);

            $ownerType = $config->get('owner_type');
            $ownerFieldName = $config->get('owner_field_name');
            // set default owner
            if ($ownerType && !$accessor->getValue($entity, $ownerFieldName)) {
                $owner = $this->getOwner($ownerType);
                if ($owner) {
                    $accessor->setValue(
                        $entity,
                        $ownerFieldName,
                        $owner
                    );
                }
            }

            $organizationFieldName = $config->get('organization_field_name');
            //set organization
            if ($organizationFieldName && !$accessor->getValue($entity, $organizationFieldName)) {
                $organization = $this->getOrganization();
                if ($organization) {
                    $accessor->setValue(
                        $entity,
                        $organizationFieldName,
                        $organization
                    );
                }
            }
        }
    }

    /**
     * @param string $ownerType
     *
     * @return object
     */
    protected function getOwner(string $ownerType)
    {
        $owner = null;
        $user = $this->getUser();

        if (OwnershipType::OWNER_TYPE_USER === $ownerType) {
            $owner = $user;
        }

        if (OwnershipType::OWNER_TYPE_BUSINESS_UNIT === $ownerType && $user instanceof User) {
            $owners = $user->getBusinessUnits()
                ->filter(function (BusinessUnit $businessUnit) {
                    return $businessUnit->getOrganization()->getId() === $this->getOrganization()->getId();
                });
            if (!$owners->isEmpty()) {
                $owner = $owners->first();
            }
        }

        if (OwnershipType::OWNER_TYPE_ORGANIZATION === $ownerType) {
            $owner = $this->getOrganization();
        }

        return $owner;
    }

    /**
     * @return Organization
     */
    protected function getOrganization()
    {
        return $this->tokenAccessor->getOrganization();
    }

    /**
     * @return User
     */
    private function getUser(): User
    {
        $user = $this->tokenAccessor->getUser();
        if ($user instanceof User) {
            return $user;
        }

        return $user->getOwner();
    }
}
