<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

class RecordOwnerDataListener
{
    /** @var ServiceLink */
    protected $securityContextLink;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param ServiceLink    $securityContextLink
     * @param ConfigProvider $configProvider
     */
    public function __construct(ServiceLink $securityContextLink, ConfigProvider $configProvider)
    {
        $this->securityContextLink = $securityContextLink;
        $this->configProvider  = $configProvider;
    }

    /**
     * Handle prePersist.
     *
     * @param LifecycleEventArgs $args
     * @throws \LogicException when getOwner method isn't implemented for entity with ownership type
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $token = $this->getSecurityContext()->getToken();
        if (!$token) {
            return;
        }
        $user = $token->getUser();
        if (!$user) {
            return;
        }
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
                $owner = null;
                if (OwnershipType::OWNER_TYPE_USER == $ownerType) {
                    $owner = $user;
                } elseif (OwnershipType::OWNER_TYPE_ORGANIZATION == $ownerType
                    && $token instanceof OrganizationContextTokenInterface
                ) {
                    $owner = $token->getOrganizationContext();
                }
                $accessor->setValue(
                    $entity,
                    $ownerFieldName,
                    $owner
                );
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
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->securityContextLink->getService();
    }
}
