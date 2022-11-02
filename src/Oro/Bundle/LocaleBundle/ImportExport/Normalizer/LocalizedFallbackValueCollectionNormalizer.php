<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\CollectionNormalizer;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Normalizes objects which implements AbstractLocalizedFallbackValue class.
 */
class LocalizedFallbackValueCollectionNormalizer extends CollectionNormalizer
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $localizedFallbackValueClass;

    /** @var string */
    protected $localizationClass;

    /** @var Localization */
    protected $localization;

    /** @var Localization[] */
    protected $localizations = [];

    /** @var bool[] */
    protected $isApplicable = [];

    /**
     * @param ManagerRegistry $registry
     * @param string $localizedFallbackValueClass
     * @param string $localizationClass
     */
    public function __construct(ManagerRegistry $registry, $localizedFallbackValueClass, $localizationClass)
    {
        $this->registry = $registry;
        $this->localizedFallbackValueClass = $localizedFallbackValueClass;
        $this->localizationClass = $localizationClass;

        $this->localization = new $localizationClass;
    }

    /** {@inheritdoc} */
    public function normalize($object, string $format = null, array $context = [])
    {
        $result = [];

        foreach ($object as $item) {
            $result[LocalizationCodeFormatter::formatName($item->getLocalization())] = [
                'fallback' => $item->getFallback(),
                'string' => $item->getString(),
                'text' => $item->getText(),
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (!is_array($data)) {
            return new ArrayCollection();
        }

        $itemType = $this->getItemType($type);
        if (!$itemType) {
            return new ArrayCollection($data);
        }

        $result = new ArrayCollection();
        if (!isset($data[LocalizationCodeFormatter::DEFAULT_LOCALIZATION])) {
            // Default localized fallback value should be always present.
            $data[LocalizationCodeFormatter::DEFAULT_LOCALIZATION] = [];
        }

        foreach ($data as $localizationName => $item) {
            $result->set($localizationName, $this->createLocalizedFallbackValue($itemType, $item, $localizationName));
        }

        return $result;
    }

    protected function createLocalizedFallbackValue(
        string $className,
        array $item,
        string $localizationName
    ): AbstractLocalizedFallbackValue {
        // Creates new object instead of clone because cloned object could have extended fields with excessive data.
        /** @var AbstractLocalizedFallbackValue $object */
        $object = new $className();

        $object->setLocalization($this->getLocalization($localizationName));

        if (array_key_exists('fallback', $item)) {
            $object->setFallback($item['fallback'] ? (string)$item['fallback'] : null);
        }

        if (array_key_exists('text', $item)) {
            $object->setText((string)$item['text']);
        }

        if (array_key_exists('string', $item)) {
            $object->setString((string)$item['string']);
        }

        return $object;
    }

    protected function getLocalization(string $localizationName): ?Localization
    {
        if ($localizationName !== LocalizationCodeFormatter::DEFAULT_LOCALIZATION
            && !array_key_exists($localizationName, $this->localizations)) {
            $this->localizations[$localizationName] = clone $this->localization;
            $this->localizations[$localizationName]->setName($localizationName);
        }

        return $this->localizations[$localizationName] ?? null;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        if (!parent::supportsNormalization($data, $format, $context)) {
            return false;
        }

        return $this->isApplicable($context);
    }

    /** {@inheritdoc} */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        if (!parent::supportsDenormalization($data, $type, $format, $context)) {
            return false;
        }

        return $this->isApplicable($context);
    }

    /**
     * @param array $context
     * @return bool
     */
    protected function isApplicable(array $context = [])
    {
        if (!isset($context['entityName'], $context['fieldName'])) {
            return false;
        }

        $className = $context['entityName'];
        $fieldName = $context['fieldName'];

        $key = $className . ':' . $fieldName;
        if (array_key_exists($key, $this->isApplicable)) {
            return $this->isApplicable[$key];
        }

        $metadata = $this->registry->getManagerForClass($className)->getClassMetadata($className);
        if (!$metadata->hasAssociation($fieldName)) {
            $this->isApplicable[$key] = false;

            return false;
        }

        $targetClass = $metadata->getAssociationTargetClass($fieldName);

        $this->isApplicable[$key] = is_a($targetClass, $this->localizedFallbackValueClass, true);

        return $this->isApplicable[$key];
    }
}
