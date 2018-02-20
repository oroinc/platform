<?php

namespace Oro\Bundle\OrganizationBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

class OrganizationExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_owner_type';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->container->get('oro_entity_config.config_manager');
    }

    /**
     * @return EntityOwnerAccessor
     */
    protected function getOwnerAccessor()
    {
        return $this->container->get('oro_security.owner.entity_owner_accessor');
    }

    /**
     * @return BusinessUnitManager
     */
    protected function getBusinessUnitManager()
    {
        return $this->container->get('oro_organization.business_unit_manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_get_owner_type', [$this, 'getOwnerType']),
            new \Twig_SimpleFunction('oro_get_entity_owner', [$this, 'getEntityOwner']),
            new \Twig_SimpleFunction('oro_get_owner_field_name', [$this, 'getOwnerFieldName']),
            new \Twig_SimpleFunction('oro_get_business_units_count', [$this, 'getBusinessUnitCount']),
        ];
    }

    /**
     * @param object $entity
     *
     * @return string
     */
    public function getOwnerType($entity)
    {
        $ownerClassName = ClassUtils::getRealClass($entity);

        $configManager = $this->getConfigManager();
        if (!$configManager->hasConfig($ownerClassName)) {
            return null;
        }

        return $configManager->getEntityConfig('ownership', $ownerClassName)->get('owner_type');
    }

    /**
     * @param object $entity
     *
     * @return string
     */
    public function getOwnerFieldName($entity)
    {
        $ownerClassName = ClassUtils::getRealClass($entity);

        $configManager = $this->getConfigManager();
        if (!$configManager->hasConfig($ownerClassName)) {
            return null;
        }

        return $configManager->getEntityConfig('ownership', $ownerClassName)->get('owner_field_name');
    }

    /**
     * @param object $entity
     *
     * @return null|object
     */
    public function getEntityOwner($entity)
    {
        return $this->getOwnerAccessor()->getOwner($entity);
    }

    /**
     * @return int
     */
    public function getBusinessUnitCount()
    {
        return $this->getBusinessUnitManager()->getBusinessUnitRepo()->getBusinessUnitsCount();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::EXTENSION_NAME;
    }
}
