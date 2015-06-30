<?php

namespace Oro\Bundle\TrackingBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;

use Oro\Bundle\TrackingBundle\Migration\Extension\IdentifierEventExtension;
use Oro\Bundle\TrackingBundle\Provider\TrackingEventIdentificationProvider;

class IdentifierVisitConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var  TrackingEventIdentificationProvider */
    protected $identifyProvider;

    /** @var array */
    protected $targetEntities;

    /** @var ConfigManager */
    protected $configManager;

    /** @var AssociationBuilder */
    protected $associationBuilder;

    /**
     * @param TrackingEventIdentificationProvider $identifyProvider
     * @param ConfigManager                       $configManager
     * @param AssociationBuilder                  $associationBuilder
     */
    public function __construct(
        TrackingEventIdentificationProvider $identifyProvider,
        ConfigManager $configManager,
        AssociationBuilder $associationBuilder
    ) {
        $this->identifyProvider   = $identifyProvider;
        $this->configManager      = $configManager;
        $this->associationBuilder = $associationBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        if ($actionType === ExtendConfigDumper::ACTION_PRE_UPDATE) {
            $targetEntities = $this->getTargetEntities();
            if (empty($targetEntities)) {
                return false;
            }

            return $this->configManager
                ->getProvider('extend')
                ->hasConfig('Oro\Bundle\TrackingBundle\Entity\TrackingVisit');
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $targetEntities = $this->getTargetEntities();
        foreach ($targetEntities as $targetEntity) {
            $this->associationBuilder->createManyToOneAssociation(
                'Oro\Bundle\TrackingBundle\Entity\TrackingVisit',
                $targetEntity,
                IdentifierEventExtension::ASSOCIATION_KIND
            );
        }
    }

    /**
     * Gets the list of configs for entities which can be the target of the association
     *
     * @return array
     */
    protected function getTargetEntities()
    {
        if (null === $this->targetEntities) {
            $targetEntityClasses = $this->identifyProvider->getTargetIdentityEntities();
            $configs             = $this->configManager->getProvider('extend')->getConfigs();
            foreach ($configs as $config) {
                if ($config->is('upgradeable')
                    && in_array($config->getId()->getClassName(), $targetEntityClasses)
                ) {
                    $this->targetEntities[] = $config->getId()->getClassName();
                }
            }
        }

        return $this->targetEntities;
    }
}
