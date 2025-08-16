<?php

namespace Oro\Bundle\LocaleBundle\Cache\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Normalizes localized fallback value entity for usage in cache.
 */
class LocalizedFallbackValueNormalizer implements ResetInterface
{
    private const string LOCALIZATION = 'localization';

    private array $metadata = [];

    public function __construct(
        private readonly array $nameMap,
        private readonly ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function reset(): void
    {
        $this->metadata = [];
    }

    public function normalize(AbstractLocalizedFallbackValue $localizedFallbackValue): array
    {
        $normalizedData = [];
        $classMetadata = $this->getClassMetadata(ClassUtils::getClass($localizedFallbackValue));
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $value = $classMetadata->getFieldValue($localizedFallbackValue, $fieldName);
            if (null !== $value) {
                $normalizedData[$this->nameMap[$fieldName] ?? $fieldName] = $value;
            }
        }

        $localization = $localizedFallbackValue->getLocalization();
        if (null !== $localization) {
            $normalizedData[$this->nameMap[self::LOCALIZATION] ?? self::LOCALIZATION] = $localization->getId();
        }

        return $normalizedData;
    }

    public function denormalize(array $normalizedData, string $entityClass): AbstractLocalizedFallbackValue
    {
        foreach ($this->nameMap as $name => $key) {
            if (isset($normalizedData[$key])) {
                $normalizedData[$name] = $normalizedData[$key];
                unset($normalizedData[$key]);
            }
        }

        $classMetadata = $this->getClassMetadata(ClassUtils::getRealClass($entityClass));
        /** @var AbstractLocalizedFallbackValue $localizedFallbackValue */
        $localizedFallbackValue = $classMetadata->newInstance();
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            if (isset($normalizedData[$fieldName])) {
                $classMetadata->setFieldValue($localizedFallbackValue, $fieldName, $normalizedData[$fieldName]);
            }
        }

        $localizationData = $normalizedData[self::LOCALIZATION] ?? null;
        if ($localizationData) {
            $localizedFallbackValue->setLocalization($this->getEntityManager(Localization::class)->getReference(
                Localization::class,
                \is_array($localizationData) ? $localizationData['id'] : $localizationData
            ));
        }

        return $localizedFallbackValue;
    }

    private function getClassMetadata(string $entityClass): ClassMetadata
    {
        if (!isset($this->metadata[$entityClass])) {
            $this->metadata[$entityClass] = $this->getEntityManager($entityClass)?->getClassMetadata($entityClass);
        }

        return $this->metadata[$entityClass];
    }

    private function getEntityManager(string $entityClass): ObjectManager
    {
        return $this->doctrine->getManagerForClass($entityClass);
    }
}
