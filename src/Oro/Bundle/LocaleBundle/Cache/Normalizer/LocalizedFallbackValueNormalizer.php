<?php

namespace Oro\Bundle\LocaleBundle\Cache\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Normalizes localized fallback value entity for usage in cache.
 */
class LocalizedFallbackValueNormalizer implements ResetInterface
{
    private ManagerRegistry $managerRegistry;

    private array $classMetadataCache = [];

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param AbstractLocalizedFallbackValue $localizedFallbackValue
     *
     * @return array
     *  [
     *      'string' => ?string,
     *      'fallback' => ?string,
     *      'localization' => ?array [
     *          'id' => int
     *      ],
     *      // ...
     *  ]
     */
    public function normalize(AbstractLocalizedFallbackValue $localizedFallbackValue): array
    {
        $classMetadata = $this->getClassMetadata(ClassUtils::getRealClass(get_class($localizedFallbackValue)));
        $normalizedData = [];
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $value = $classMetadata->getFieldValue($localizedFallbackValue, $fieldName);
            if ($value !== null) {
                $normalizedData[$fieldName] = $value;
            }
        }

        $localization = $localizedFallbackValue->getLocalization();
        if ($localization !== null) {
            $normalizedData['localization'] = ['id' => $localization->getId()];
        }

        return $normalizedData;
    }

    /**
     * @param array $normalizedData
     *  [
     *      'string' => ?string,
     *      'fallback' => ?string,
     *      'localization' => ?array [
     *          'id' => int
     *      ],
     *      // ...
     *  ]
     * @param string $entityClass
     *
     * @return AbstractLocalizedFallbackValue
     */
    public function denormalize(array $normalizedData, string $entityClass): AbstractLocalizedFallbackValue
    {
        $entityClass = ClassUtils::getRealClass($entityClass);
        $classMetadata = $this->getClassMetadata($entityClass);

        /** @var AbstractLocalizedFallbackValue $localizedFallbackValue */
        $localizedFallbackValue = $classMetadata->newInstance();
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            if (isset($normalizedData[$fieldName])) {
                $classMetadata->setFieldValue($localizedFallbackValue, $fieldName, $normalizedData[$fieldName]);
            }
        }

        if (isset($normalizedData['localization']['id'])) {
            $localization = $this->managerRegistry
                ->getManagerForClass(Localization::class)
                ->getReference(Localization::class, (int)$normalizedData['localization']['id']);
            $localizedFallbackValue->setLocalization($localization);
        }

        return $localizedFallbackValue;
    }

    private function getClassMetadata(string $className): ClassMetadata
    {
        if (!isset($this->classMetadataCache[$className])) {
            $this->classMetadataCache[$className] = $this->managerRegistry
                ->getManagerForClass($className)
                ?->getClassMetadata($className);
        }

        return $this->classMetadataCache[$className];
    }

    public function reset(): void
    {
        $this->classMetadataCache = [];
    }
}
