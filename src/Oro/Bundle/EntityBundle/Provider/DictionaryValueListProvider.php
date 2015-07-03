<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;

class DictionaryValueListProvider implements DictionaryValueListProviderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var AssociationTypeHelper */
    protected $typeHelper;

    /**
     * @param ConfigManager         $configManager
     * @param ManagerRegistry       $doctrine
     * @param AssociationTypeHelper $typeHelper
     */
    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        AssociationTypeHelper $typeHelper
    ) {
        $this->configManager = $configManager;
        $this->doctrine      = $doctrine;
        $this->typeHelper    = $typeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($className)
    {
        return $this->typeHelper->isDictionary($className);
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

        return [
            'exclusion_policy' => 'all',
            'fields'           => $fields
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedEntityClasses()
    {
        return $this->typeHelper->getOwningSideEntities(GroupingScope::GROUP_DICTIONARY);
    }
}
