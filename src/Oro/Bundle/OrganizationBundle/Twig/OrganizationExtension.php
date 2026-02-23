<?php

namespace Oro\Bundle\OrganizationBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to deal with entity owners:
 *   - oro_get_owner_type
 *   - oro_get_entity_owner
 *   - oro_get_owner_field_name
 *   - oro_get_business_units_count
 */
class OrganizationExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_get_owner_type', [$this, 'getOwnerType']),
            new TwigFunction('oro_get_entity_owner', [$this, 'getEntityOwner']),
            new TwigFunction('oro_get_owner_field_name', [$this, 'getOwnerFieldName']),
            new TwigFunction('oro_get_business_units_count', [$this, 'getBusinessUnitCount']),
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

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            EntityOwnerAccessor::class,
            BusinessUnitManager::class,
            ConfigManager::class
        ];
    }

    private function getOwnerAccessor(): EntityOwnerAccessor
    {
        return $this->container->get(EntityOwnerAccessor::class);
    }

    private function getBusinessUnitManager(): BusinessUnitManager
    {
        return $this->container->get(BusinessUnitManager::class);
    }

    private function getConfigManager(): ConfigManager
    {
        return $this->container->get(ConfigManager::class);
    }
}
