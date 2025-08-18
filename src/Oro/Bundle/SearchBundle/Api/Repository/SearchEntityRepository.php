<?php

namespace Oro\Bundle\SearchBundle\Api\Repository;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Api\Model\SearchEntity;
use Oro\Bundle\SearchBundle\Api\SearchEntityClassProviderInterface;
use Oro\Bundle\SearchBundle\Api\SearchMappingProvider;
use Oro\Bundle\UIBundle\Tools\EntityLabelBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The repository to get entities available for the search API resource.
 */
class SearchEntityRepository
{
    public function __construct(
        private readonly SearchEntityClassProviderInterface $searchEntityClassProvider,
        private readonly ValueNormalizer $valueNormalizer,
        private readonly ConfigManager $configManager,
        private readonly TranslatorInterface $translator,
        private readonly SearchMappingProvider $searchMappingProvider
    ) {
    }

    /**
     * @return SearchEntity[]
     */
    public function getSearchEntities(
        string $version,
        RequestType $requestType,
        ?array $entityClasses = null,
        ?bool $searchable = null
    ): array {
        $result = [];
        $accessibleEntityClasses = $this->searchEntityClassProvider->getAccessibleEntityClasses($version, $requestType);
        $allowedEntityClasses = $this->searchEntityClassProvider->getAllowedEntityClasses($version, $requestType);
        if ($entityClasses) {
            $accessibleEntityClasses = array_intersect_key($accessibleEntityClasses, array_flip($entityClasses));
        }
        foreach ($accessibleEntityClasses as $entityClass => $searchAlias) {
            $isSearchableEntity = isset($allowedEntityClasses[$entityClass]);
            if (null !== $searchable && $searchable !== $isSearchableEntity) {
                continue;
            }
            $result[] = $this->getSearchEntity($entityClass, $requestType, $isSearchableEntity);
        }

        return $result;
    }

    public function findSearchEntity(string $entityType, string $version, RequestType $requestType): ?SearchEntity
    {
        $entityClass = ValueNormalizerUtil::tryConvertToEntityClass($this->valueNormalizer, $entityType, $requestType);
        if (!$entityClass) {
            return null;
        }

        $isEntityExist = false;
        $accessibleEntityClasses = $this->searchEntityClassProvider->getAccessibleEntityClasses($version, $requestType);
        foreach ($accessibleEntityClasses as $class => $searchAlias) {
            if ($class === $entityClass) {
                $isEntityExist = true;
                break;
            }
        }

        if (!$isEntityExist) {
            return null;
        }

        $allowedEntityClasses = $this->searchEntityClassProvider->getAllowedEntityClasses($version, $requestType);

        return $this->getSearchEntity($entityClass, $requestType, isset($allowedEntityClasses[$entityClass]));
    }

    private function getSearchEntity(
        string $entityClass,
        RequestType $requestType,
        bool $isSearchableEntity
    ): SearchEntity {
        return new SearchEntity(
            ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $entityClass, $requestType),
            $this->translator->trans($this->getEntityLabel($entityClass)),
            $isSearchableEntity,
            $isSearchableEntity ? $this->searchMappingProvider->getSearchFields($entityClass) : []
        );
    }

    private function getEntityLabel(string $entityClass): string
    {
        if (!$this->configManager->hasConfig($entityClass)) {
            return EntityLabelBuilder::getEntityLabelTranslationKey($entityClass);
        }

        return $this->configManager->getEntityConfig('entity', $entityClass)->get('label');
    }
}
