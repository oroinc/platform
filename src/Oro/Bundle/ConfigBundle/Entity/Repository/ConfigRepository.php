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
     * @param mixed $scopeEntityIdentifier
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
     * @param string $scopeEntityName
     * @param mixed  $scopeEntityIdentifier
     *
     * @return Config
     */
    public function getByEntity($scopeEntityName, $scopeEntityIdentifier)
    {
        $config = $this->findOneBy(['scopedEntity' => $scopeEntityName, 'recordId' => $scopeEntityIdentifier]);

        if (!$config) {
            $config = new Config();
            $config->setEntity($scopeEntityName)
                ->setRecordId($scopeEntityIdentifier);
        }

        return $config;
    }
}
