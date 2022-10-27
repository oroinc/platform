<?php

namespace Oro\Bundle\SearchBundle\Api\Repository;

use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Api\Model\SearchEntity;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\UIBundle\Tools\EntityLabelBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The repository to get entities available for the search API resource.
 */
class SearchEntityRepository
{
    private SearchMappingProvider $searchMappingProvider;
    private Indexer $searchIndexer;
    private ResourcesProvider $resourcesProvider;
    private ValueNormalizer $valueNormalizer;
    private ConfigManager $configManager;
    private TranslatorInterface $translator;

    public function __construct(
        SearchMappingProvider $searchMappingProvider,
        Indexer $searchIndexer,
        ResourcesProvider $resourcesProvider,
        ValueNormalizer $valueNormalizer,
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->searchMappingProvider = $searchMappingProvider;
        $this->searchIndexer = $searchIndexer;
        $this->resourcesProvider = $resourcesProvider;
        $this->valueNormalizer = $valueNormalizer;
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     * @param bool|null   $searchable
     *
     * @return SearchEntity[]
     */
    public function getSearchEntities(string $version, RequestType $requestType, ?bool $searchable = null): array
    {
        $result = [];
        $searchAliases = $this->searchMappingProvider->getEntitiesListAliases();
        $allowedSearchAliases = $this->searchIndexer->getAllowedEntitiesListAliases();
        foreach ($searchAliases as $entityClass => $searchAlias) {
            if (!$this->resourcesProvider->isResourceAccessible($entityClass, $version, $requestType)) {
                continue;
            }
            $isSearchableEntity = isset($allowedSearchAliases[$entityClass]);
            if (null !== $searchable && $searchable !== $isSearchableEntity) {
                continue;
            }
            $result[] = new SearchEntity(
                ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $entityClass, $requestType),
                $this->translator->trans($this->getEntityLabel($entityClass)),
                $isSearchableEntity
            );
        }

        return $result;
    }

    private function getEntityLabel(string $entityClass): string
    {
        if (!$this->configManager->hasConfig($entityClass)) {
            return EntityLabelBuilder::getEntityLabelTranslationKey($entityClass);
        }

        return $this->configManager->getEntityConfig('entity', $entityClass)->get('label');
    }
}
