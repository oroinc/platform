<?php

namespace Oro\Bundle\ConfigBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;

/**
 * Class ConfigRepository
 *
 * @package Oro\Bundle\ConfigBundle
 */
class ConfigRepository extends EntityRepository
{
    /**
     * Load settings from database for given
     *
     * @param string $scopeEntityName
     * @param string $scopeEntityIdentifier
     *
     * @return array
     */
    public function loadSettings($scopeEntityName, $scopeEntityIdentifier)
    {
        $scope = $this->getByEntity($scopeEntityName, $scopeEntityIdentifier);

        $settings = [];
        /** @var ConfigValue $value */
        foreach ($scope->getValues() as $value) {
            $settings[$value->getSection()][$value->getName()] = [
                'value'                  => $value->getValue(),
                'scope'                  => $scope->getEntity(),
                'use_parent_scope_value' => false
            ];
        }

        return $settings;
    }

    /**
     * @param $entityName
     * @param $scopeId
     *
     * @return Config
     */
    public function getByEntity($entityName, $scopeId)
    {
        $config = $this->findOneBy(['scopedEntity' => $entityName, 'recordId' => $scopeId]);

        if (!$config) {
            $config = new Config();
            $config->setEntity($entityName)
                ->setRecordId($scopeId);
        }

        return $config;
    }
}
