<?php

namespace Oro\Bundle\ActivityBundle\Helper;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ActivityInheritanceTargetsHelper
{
    protected $configManager;

    protected $entityManager;

    public function __construct(ConfigManager $configManager, OroEntityManager $entityManager)
    {
        $this->configManager = $configManager;
        $this->entityManager = $entityManager;
    }

    public function getInheritanceTargetsRelations($entityClass)
    {
        if ($this->configManager->hasConfigEntityModel($entityClass)) {
            $configValues = $this->configManager->getEntityConfig('activity', $entityClass)->getValues();
            if (isset($configValues['inheritance_targets'])) {
                $inheritanceTargets = $configValues['inheritance_targets'];
                $inheritanceTargets = is_array($inheritanceTargets) ? $inheritanceTargets : [$inheritanceTargets];

                $result = [];
                foreach ($inheritanceTargets as $target) {
                    $this->getAssociationChain($entityClass, $target);
                    $result[] = [
                        'id' => [],
                        'classTarget' => $this->getAssociationName($target)
                    ];
                    $temp = 0;
                }
                $temp = 0;
            }
        }

    }

    public function getAssociationChain($entityClass, $targetClass)
    {
        $metadata = $this->entityManager->getClassMetadata($entityClass);
        $associationMappings = $metadata->getAssociationMappings();

        $result = [];
        foreach ($associationMappings as $mapping) {
            if (isset($mapping['targetEntity'])) {
                if ($mapping['targetEntity'] === $targetClass) {
                    $result[] = $targetClass;
                    break;
                }
            }
        }

        if (empty($result)) {
            $targetMetadata = $this->entityManager->getClassMetadata($targetClass);
            $targetAssociationMappings = $targetMetadata->getAssociationMappings();
            foreach ($associationMappings as $mapping) {
                if (isset($mapping['targetEntity'])) {
                    foreach ($targetAssociationMappings as $targetMapping) {
                        if (isset($targetMapping['targetEntity']) && $targetMapping['targetEntity'] === $mapping['targetEntity']) {
                            $result[] = $mapping['targetEntity'];
                            $result[] = $targetClass;

                            return $result;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get Association name
     *
     * @param string $className
     *
     * @return string
     */
    protected function getAssociationName($className)
    {
        return ExtendHelper::buildAssociationName(
            $className,
            ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
        );
    }
}
