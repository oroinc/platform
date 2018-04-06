<?php

namespace Oro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class RecordOwnerDataListener
{
    /** @var TokenAccessor*/
    protected $tokenAccessor;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param TokenAccessor $tokenAccessor
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
     * @throws \LogicException when getOwner method isn't implemented for entity with ownership type
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        if (!$this->tokenAccessor->hasUser()) {
            return;
        }

        $token = $this->tokenAccessor->getToken();
        $entity    = $args->getEntity();
        $className = ClassUtils::getClass($entity);
        if ($this->configProvider->hasConfig($className)) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $config = $this->configProvider->getConfig($className);
            $ownerType = $config->get('owner_type');
            $ownerFieldName = $config->get('owner_field_name');
            // set default owner for organization and user owning entities
            if ($ownerType
                && in_array($ownerType, [OwnershipType::OWNER_TYPE_ORGANIZATION, OwnershipType::OWNER_TYPE_USER])
                && !$accessor->getValue($entity, $ownerFieldName)
            ) {
                $this->setOwner($ownerType, $entity, $token, $ownerFieldName);
            }
            //set organization
            $this->setDefaultOrganization($token, $config, $entity);
        }
    }

    /**
     * @param TokenInterface  $token
     * @param ConfigInterface $config
     * @param object          $entity
     */
    protected function setDefaultOrganization(TokenInterface $token, ConfigInterface $config, $entity)
    {
        if ($token instanceof OrganizationContextTokenInterface && $config->has('organization_field_name')) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $fieldName = $config->get('organization_field_name');
            if (!$accessor->getValue($entity, $fieldName)) {
                $accessor->setValue(
                    $entity,
                    $fieldName,
                    $token->getOrganizationContext()
                );
            }
        }
    }

    /**
     * @param string $ownerType
     * @param object $entity
     * @param TokenInterface $token
     * @param string $ownerFieldName
     */
    protected function setOwner($ownerType, $entity, TokenInterface $token, $ownerFieldName)
    {
        $user = $token->getUser();
        $accessor = PropertyAccess::createPropertyAccessor();
        $owner = null;
        if (OwnershipType::OWNER_TYPE_USER == $ownerType) {
            $owner = null;
            if ($user instanceof User) {
                $owner = $user;
            } elseif ($user->getOwner() instanceof User) {
                $owner = $user->getOwner();
            }
        }
        if (OwnershipType::OWNER_TYPE_ORGANIZATION == $ownerType
            && $token instanceof OrganizationContextTokenInterface
        ) {
            $owner = $token->getOrganizationContext();
        }
        if ($owner) {
            $accessor->setValue(
                $entity,
                $ownerFieldName,
                $owner
            );
        }
    }
}
