<?php

namespace Oro\Bundle\OrganizationBundle\Twig;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;

class OwnerTypeExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_owner_type';

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /** @var EntityOwnerAccessor */
    protected $ownerAccessor;

    /**
     * @param ConfigProvider      $configProvider
     * @param EntityOwnerAccessor $entityOwnerAccessor
     */
    public function __construct(ConfigProvider $configProvider, EntityOwnerAccessor $entityOwnerAccessor)
    {
        $this->configProvider = $configProvider;
        $this->ownerAccessor = $entityOwnerAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'oro_get_owner_type' => new \Twig_Function_Method($this, 'getOwnerType'),
            'oro_get_entity_owner' => new \Twig_Function_Method($this, 'getEntityOwner'),
            'oro_get_user_owner_owning_business_unit' => new \Twig_SimpleFunction(
                'oro_get_user_owner_owning_business_unit',
                [$this, 'getUserOwnerOwningBusinessUnit']
            ),
        ];
    }

    /**
     * @param object $entity
     * @return string
     */
    public function getOwnerType($entity)
    {
        $ownerClassName = ClassUtils::getRealClass($entity);
        if (!$this->configProvider->hasConfig($ownerClassName)) {
            return null;
        }
        $config = $this->configProvider->getConfig($ownerClassName);

        return $config->get('owner_type');
    }

    /**
     * @param object $entity
     *
     * @return null|object
     */
    public function getEntityOwner($entity)
    {
        return $this->ownerAccessor->getOwner($entity);
    }

    /**
     * Gets entity owner business unit if ownership type of this entity is 'USER'
     *
     * @param object $entity
     *
     * @return BusinessUnit|null
     */
    public function getUserOwnerOwningBusinessUnit($entity)
    {
        $ownerClassName = ClassUtils::getRealClass($entity);
        if (!$this->configProvider->hasConfig($ownerClassName)) {
            return null;
        }
        $config = $this->configProvider->getConfig($ownerClassName);
        if ($config->get('owner_type') !== 'USER') {
            return null;
        }
        $propertyAccessor = new PropertyAccessor();
        $user = $propertyAccessor->getValue($entity, $config->get('owner_field_name'));

        $owner = $this->ownerAccessor->getOwner($user);

        return $owner;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::EXTENSION_NAME;
    }
}
