<?php

namespace Oro\Bundle\ConfigBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;

/**
 * Class ConfigRepository
 * @package Oro\Bundle\ConfigBundle
 */
class ConfigRepository extends EntityRepository
{
    /**
     * @param string $entity
     * @param string $entityId
     * @param string $section
     * @return array
     */
    public function loadSettings($entity, $entityId, $section)
    {
        $criteria = array(
            'scopedEntity' => $entity,
            'recordId'     => $entityId,
        );

        if (!is_null($section)) {
            $criteria['section'] = $section;
        }

        $scope = $this->findOneBy($criteria);
        if (!$scope) {
            return array();
        }

        $settings = array();
        foreach ($scope->getValues() as $value) {
            $settings[$value->getSection()][$value->getName()] = array(
                'value' => $this->getValue($value),
                'scope' => $scope->getEntity() ?: 'app',
                'use_parent_scope_value' => false
            );
        }

        return $settings;
    }

    /**
     * @param ConfigValue $value
     *
     * @return array|string
     */
    protected function getValue(ConfigValue $value)
    {
        switch ($value->getType()) {
            case ConfigValue::FIELD_SERIALIZED_TYPE:
                $result = unserialize($value->getValue());
                break;
            case ConfigValue::FIELD_LIST_TYPE:
                $result = explode(ConfigValue::DELIMITER, $value->getValue());
                break;
            default:
                $result = $value->getValue();
        }

        return $result;
    }

    /**
     * @param $entityName
     * @param $scopeId
     * @return Config
     */
    public function getByEntity($entityName, $scopeId)
    {
        $config = $this->findOneBy(array('scopedEntity' => $entityName, 'recordId' => $scopeId));

        if (!$config) {
            $config = new Config();
            $config->setEntity($entityName)
                ->setRecordId($scopeId);
        }

        return $config;
    }
}
