<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Provider\DictionaryValueListProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides information about enum entities.
 */
class EnumValueListProvider implements DictionaryValueListProviderInterface
{
    private ConfigManager $configManager;
    private ManagerRegistry $doctrine;

    public function __construct(ConfigManager $configManager, ManagerRegistry $doctrine)
    {
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $className): bool
    {
        return
            $this->configManager->hasConfig($className)
            && ExtendHelper::isEnumValueEntityAccessible($this->configManager->getEntityConfig('extend', $className));
    }

    /**
     * {@inheritDoc}
     */
    public function getValueListQueryBuilder(string $className): QueryBuilder
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass($className);

        return $em->createQueryBuilder()
            ->select('e')
            ->from($className, 'e');
    }

    /**
     * {@inheritDoc}
     */
    public function getSerializationConfig(string $className): array
    {
        $fields = [];
        $metadata = $this->doctrine->getManagerForClass($className)->getClassMetadata($className);
        foreach ($metadata->getFieldNames() as $fieldName) {
            $fieldConfig = $this->configManager->getFieldConfig('extend', $className, $fieldName);
            if ($fieldConfig->is('is_extend')) {
                // skip extended fields
                continue;
            }

            $fields[$fieldName] = null;
        }
        $fields['order'] = ['property_path' => 'priority'];
        unset($fields['priority']);

        return [
            'exclusion_policy' => 'all',
            'hints'            => ['HINT_TRANSLATABLE'],
            'fields'           => $fields
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedEntityClasses(): array
    {
        $result = [];
        $entityConfigs = $this->configManager->getConfigs('extend', null, true);
        foreach ($entityConfigs as $entityConfig) {
            if (ExtendHelper::isEnumValueEntityAccessible($entityConfig)) {
                $result[] = $entityConfig->getId()->getClassName();
            }
        }

        return $result;
    }
}
