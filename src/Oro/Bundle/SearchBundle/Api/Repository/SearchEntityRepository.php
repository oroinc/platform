<?php

namespace Oro\Bundle\SearchBundle\Api\Repository;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Api\Model\SearchEntity;
use Oro\Bundle\SearchBundle\Api\SearchEntityClassProviderInterface;
use Oro\Bundle\UIBundle\Tools\EntityLabelBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The repository to get entities available for the search API resource.
 */
class SearchEntityRepository
{
    private SearchEntityClassProviderInterface $searchEntityClassProvider;
    private ValueNormalizer $valueNormalizer;
    private ConfigManager $configManager;
    private TranslatorInterface $translator;

    public function __construct(
        SearchEntityClassProviderInterface $searchEntityClassProvider,
        ValueNormalizer $valueNormalizer,
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->searchEntityClassProvider = $searchEntityClassProvider;
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
        $accessibleEntityClasses = $this->searchEntityClassProvider->getAccessibleEntityClasses($version, $requestType);
        $allowedEntityClasses = $this->searchEntityClassProvider->getAllowedEntityClasses($version, $requestType);
        foreach ($accessibleEntityClasses as $entityClass => $searchAlias) {
            $isSearchableEntity = isset($allowedEntityClasses[$entityClass]);
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
