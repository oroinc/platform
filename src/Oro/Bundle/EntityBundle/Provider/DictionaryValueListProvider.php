<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Provides information about dictionary entities.
 */
class DictionaryValueListProvider implements DictionaryValueListProviderInterface
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
        if (!$this->configManager->hasConfig($className)) {
            return false;
        }

        $groups = $this->configManager->getEntityConfig('grouping', $className)->get('groups');

        return $groups && \in_array('dictionary', $groups, true);
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
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass($className);
        $metadata = $em->getClassMetadata($className);

        $fields = [];
        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $extendFieldConfig = $this->configManager->getFieldConfig('extend', $className, $fieldName);
            if ($extendFieldConfig->is('is_extend')) {
                // skip extended fields
                continue;
            }

            $fields[$fieldName] = null;
        }
        $associationNames = $metadata->getAssociationNames();
        foreach ($associationNames as $associationName) {
            $extendFieldConfig = $this->configManager->getFieldConfig('extend', $className, $associationName);
            if ($extendFieldConfig->is('is_extend')) {
                // skip extended fields
                continue;
            }

            $mapping = $metadata->getAssociationMapping($associationName);
            if (($mapping['type'] & ClassMetadata::TO_ONE) && $mapping['isOwningSide']) {
                $targetMetadata = $em->getClassMetadata($mapping['targetEntity']);
                $idFieldNames = $targetMetadata->getIdentifierFieldNames();
                if (\count($idFieldNames) === 1) {
                    $fields[$associationName] = ['fields' => $idFieldNames[0]];
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
     * {@inheritDoc}
     */
    public function getSupportedEntityClasses(): array
    {
        $result = [];
        $entityConfigs = $this->configManager->getConfigs('grouping', null, true);
        foreach ($entityConfigs as $entityConfig) {
            $groups = $entityConfig->get('groups');
            if ($groups && \in_array('dictionary', $groups, true)) {
                $result[] = $entityConfig->getId()->getClassName();
            }
        }

        return $result;
    }
}
