<?php

namespace Oro\Bundle\DataGridBundle\Extension\NoDataMessages;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\UIBundle\Tools\EntityLabelBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This extension add appropriate entityHint based on entity plural label
 */
class NoDataMessagesExtension extends AbstractExtension
{
    /**
     * @var EntityClassResolver
     */
    private $entityClassResolver;

    /**
     * @var AbstractSearchMappingProvider
     */
    private $mappingProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        EntityClassResolver $entityClassResolver,
        AbstractSearchMappingProvider $mappingProvider,
        TranslatorInterface $translator
    ) {
        $this->entityClassResolver = $entityClassResolver;
        $this->mappingProvider = $mappingProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $this->translateEmptyGridMessageIfExist($config);
        $this->translateEmptyFilteredGridMessageIfExist($config);

        $entityHintTranslationKey = (string) $config->offsetGetByPath(DatagridConfiguration::ENTITY_HINT_PATH);
        if ($entityHintTranslationKey) {
            $config->offsetSetByPath(
                DatagridConfiguration::ENTITY_HINT_PATH,
                $this->translator->trans($entityHintTranslationKey)
            );

            return;
        }

        $entityClassName = $this->getEntityClassNameFromQuery($config);
        if (!$entityClassName) {
            return;
        }

        $entityHintTranslationKey = EntityLabelBuilder::getEntityPluralLabelTranslationKey($entityClassName);
        $entityHint = $this->translator->trans($entityHintTranslationKey);

        $config->offsetSetByPath(DatagridConfiguration::ENTITY_HINT_PATH, $entityHint);
    }

    private function translateEmptyGridMessageIfExist(DatagridConfiguration $config): void
    {
        $emptyGridTranslationKey = (string) $config->offsetGetByPath(DatagridConfiguration::EMPTY_GRID_MESSAGE_PATH);
        if ($emptyGridTranslationKey) {
            $config->offsetSetByPath(
                DatagridConfiguration::EMPTY_GRID_MESSAGE_PATH,
                $this->translator->trans($emptyGridTranslationKey)
            );
        }
    }

    private function translateEmptyFilteredGridMessageIfExist(DatagridConfiguration $config): void
    {
        $emptyFilteredGridTranslationKey = (string) $config
            ->offsetGetByPath(DatagridConfiguration::EMPTY_FILTERED_GRID_MESSAGE_PATH);
        if ($emptyFilteredGridTranslationKey) {
            $config->offsetSetByPath(
                DatagridConfiguration::EMPTY_FILTERED_GRID_MESSAGE_PATH,
                $this->translator->trans($emptyFilteredGridTranslationKey)
            );
        }
    }

    private function getEntityClassNameFromQuery(DatagridConfiguration $config): ?string
    {
        $entityClassName = '';
        if ($config->isOrmDatasource()) {
            $entityClassName = $config->getOrmQuery()->getRootEntity($this->entityClassResolver, true);
        } elseif (SearchDatasource::TYPE === $config->getDatasourceType()) {
            if ($config->offsetExistByPath(DatagridConfiguration::FROM_PATH)) {
                $alias = $config->offsetGetByPath(DatagridConfiguration::FROM_PATH)[0];
                $entityClassName = $this->mappingProvider->getEntityClass($alias);
            }
        }

        return $entityClassName;
    }
}
