<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class DictionaryValueListProvider implements DictionaryValueListProviderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ConfigManager   $configManager
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $doctrine
    ) {
        $this->configManager = $configManager;
        $this->doctrine      = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($className)
    {
        $groupingConfigProvider = $this->configManager->getProvider('grouping');
        if (!$groupingConfigProvider->hasConfig($className)) {
            return false;
        }

        $groups = $groupingConfigProvider->getConfig($className)->get('groups');

        return !empty($groups) && in_array(GroupingScope::GROUP_DICTIONARY, $groups, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getValueListQueryBuilder($className)
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass($className);
        $qb = $em->getRepository($className)->createQueryBuilder('e');

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getSerializationConfig($className)
    {
        /** @var EntityManager $em */
        $em                   = $this->doctrine->getManagerForClass($className);
        $metadata             = $em->getClassMetadata($className);
        $extendConfigProvider = $this->configManager->getProvider('extend');

        $fields = [];
        foreach ($metadata->getFieldNames() as $fieldName) {
            $extendFieldConfig = $extendConfigProvider->getConfig($className, $fieldName);
            if ($extendFieldConfig->is('is_extend')) {
                // skip extended fields
                continue;
            }

            $fields[$fieldName] = null;
        }
        foreach ($metadata->getAssociationNames() as $fieldName) {
            $extendFieldConfig = $extendConfigProvider->getConfig($className, $fieldName);
            if ($extendFieldConfig->is('is_extend')) {
                // skip extended fields
                continue;
            }

            $mapping = $metadata->getAssociationMapping($fieldName);
            if (($mapping['type'] & ClassMetadata::TO_ONE) && $mapping['isOwningSide']) {
                $targetMetadata = $em->getClassMetadata($mapping['targetEntity']);
                $idFieldNames   = $targetMetadata->getIdentifierFieldNames();
                if (count($idFieldNames) === 1) {
                    $fields[$fieldName] = ['fields' => $idFieldNames[0]];
                }
            }
        }

        return [
            'exclusion_policy' => 'all',
            'hints'            => ['HINT_TRANSLATABLE'],
            'fields'           => $fields
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedEntityClasses()
    {
        $result = [];

        $groupingConfigProvider = $this->configManager->getProvider('grouping');
        foreach ($groupingConfigProvider->getConfigs(null, true) as $config) {
            $groups = $config->get('groups');
            if (!empty($groups) && in_array(GroupingScope::GROUP_DICTIONARY, $groups, true)) {
                $result[] = $config->getId()->getClassName();
            }
        }

        return $result;
    }
}
