<?php

namespace Oro\Bundle\EmailBundle\Api\Repository;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EmailBundle\Api\Model\EmailContextEntity;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Api\SearchEntityClassProviderInterface;
use Oro\Bundle\UIBundle\Tools\EntityLabelBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The repository to get entities available for the email context API resources.
 */
class EmailContextEntityRepository
{
    private SearchEntityClassProviderInterface $entityClassProvider;
    private ValueNormalizer $valueNormalizer;
    private ConfigManager $configManager;
    private TranslatorInterface $translator;

    public function __construct(
        SearchEntityClassProviderInterface $entityClassProvider,
        ValueNormalizer $valueNormalizer,
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->entityClassProvider = $entityClassProvider;
        $this->valueNormalizer = $valueNormalizer;
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     * @param bool|null   $allowed
     *
     * @return EmailContextEntity[]
     */
    public function getEntities(string $version, RequestType $requestType, ?bool $allowed = null): array
    {
        $result = [];
        $accessibleEntityClasses = $this->entityClassProvider->getAccessibleEntityClasses($version, $requestType);
        $allowedEntityClasses = $this->entityClassProvider->getAllowedEntityClasses($version, $requestType);
        foreach ($accessibleEntityClasses as $entityClass => $searchAlias) {
            $isAllowedEntity = isset($allowedEntityClasses[$entityClass]);
            if (null !== $allowed && $allowed !== $isAllowedEntity) {
                continue;
            }
            $result[] = new EmailContextEntity(
                ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $entityClass, $requestType),
                $this->translator->trans($this->getEntityLabel($entityClass)),
                $isAllowedEntity
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
