<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Provider\DictionaryValueListProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides information about enum option entities.
 */
class EnumOptionListProvider implements DictionaryValueListProviderInterface
{
    private ManagerRegistry $doctrine;
    private ConfigManager $configManager;
    private EntityClassProviderInterface $enumOptionEntityClassProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        ConfigManager $configManager,
        EntityClassProviderInterface $enumOptionEntityClassProvider
    ) {
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
        $this->enumOptionEntityClassProvider = $enumOptionEntityClassProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $className): bool
    {
        return ExtendHelper::isOutdatedEnumOptionEntity($className);
    }

    /**
     * {@inheritDoc}
     */
    public function getValueListQueryBuilder(string $className): QueryBuilder
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(EnumOption::class);

        return $em->createQueryBuilder()
            ->select('e')
            ->from(EnumOption::class, 'e')
            ->where('e.enumCode = :enumCode')
            ->setParameter('enumCode', ExtendHelper::getEnumCode($className));
    }

    /**
     * {@inheritDoc}
     */
    public function getSerializationConfig(string $className): array
    {
        $fields = [];
        $renameMap = ['internalId' => 'id', 'priority' => 'order'];
        $enumOptionClassName = EnumOption::class;
        $metadata = $this->doctrine->getManagerForClass($enumOptionClassName)->getClassMetadata($enumOptionClassName);
        foreach ($metadata->getFieldNames() as $fieldName) {
            if ('id' === $fieldName || 'enumCode' === $fieldName) {
                // skip internal fields
                continue;
            }
            $fieldConfig = $this->configManager->getFieldConfig('extend', $enumOptionClassName, $fieldName);
            if ($fieldConfig->is('is_extend')) {
                // skip extended fields
                continue;
            }
            if (isset($renameMap[$fieldName])) {
                $fields[$renameMap[$fieldName]] = ['property_path' => $fieldName];
            } else {
                $fields[$fieldName] = null;
            }
        }

        return [
            'exclusion_policy' => 'all',
            'hints'            => [['name' => 'HINT_ENUM_OPTION', 'value' => ExtendHelper::getEnumCode($className)]],
            'fields'           => $fields
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedEntityClasses(): array
    {
        return $this->enumOptionEntityClassProvider->getClassNames();
    }
}
