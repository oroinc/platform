<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Update all custom entities with ownership types User and Business unit.
 * Add default organization to the field organization.
 *
 * Class UpdateCustomFieldsWithOrganization
 * @package Oro\Bundle\OrganizationBundle\Migrations\Data\ORM
 */
class UpdateCustomEntitiesWithOrganization extends UpdateWithOrganization implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');
        /** @var EntityConfigId[] $entityConfigIds */
        $entityConfigIds = $configManager->getIds('extend');
        $ownerProvider = $configManager->getProvider('ownership');
        $extendProvider = $configManager->getProvider('extend');
        foreach ($entityConfigIds as $entityConfigId) {
            if ($configManager->getConfig($entityConfigId)->get('owner') == ExtendScope::OWNER_CUSTOM
                && $ownerProvider->hasConfigById($entityConfigId)
                && !$extendProvider->getConfig($entityConfigId->getClassName())
                    ->is('state', ExtendScope::STATE_NEW)
            ) {
                $className   = $entityConfigId->getClassName();
                $ownerConfig = $ownerProvider->getConfig($className);
                if (in_array($ownerConfig->get('owner_type'), ['USER', 'BUSINESS_UNIT'])) {
                    $this->update($manager, $className, 'organization', true);
                }
            }
        }
    }
}
