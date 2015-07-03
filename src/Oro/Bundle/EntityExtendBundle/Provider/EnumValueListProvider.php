<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\Provider\DictionaryValueListProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueListProvider implements DictionaryValueListProviderInterface
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
        $extendConfigProvider = $this->configManager->getProvider('extend');
        if (!$extendConfigProvider->hasConfig($className)) {
            return false;
        }
        $extendConfig = $extendConfigProvider->getConfig($className);
        if (!$this->isEnum($extendConfig)) {
            return false;
        }

        return true;
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
        $fields['priority'] = ['result_name' => 'order'];

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
        $result = [];

        $extendConfigProvider = $this->configManager->getProvider('extend');
        foreach ($extendConfigProvider->getConfigs(null, true) as $extendConfig) {
            if ($this->isEnum($extendConfig)) {
                $result[] = $extendConfig->getId()->getClassName();
            }
        }

        return $result;
    }

    /**
     * @param ConfigInterface $extendConfig
     *
     * @return bool
     */
    protected function isEnum(ConfigInterface $extendConfig)
    {
        if (!$extendConfig->is('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS)) {
            return false;
        }
        if ($extendConfig->is('state', ExtendScope::STATE_NEW) || $extendConfig->is('is_deleted')) {
            return false;
        }

        return true;
    }
}
