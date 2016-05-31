<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\CollectionNormalizer;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LocalizedFallbackValueCollectionNormalizer extends CollectionNormalizer
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $localizedFallbackValueClass;

    /** @var LocalizedFallbackValue */
    protected $value;

    /** @var string */
    protected $localeClass;

    /** @var Locale */
    protected $locale;

    /** @var Locale[] */
    protected $locales = [];

    /** @var bool[] */
    protected $isApplicable = [];

    /**
     * @param ManagerRegistry $registry
     * @param string $localizedFallbackValueClass
     * @param string $localeClass
     */
    public function __construct(ManagerRegistry $registry, $localizedFallbackValueClass, $localeClass)
    {
        $this->registry = $registry;
        $this->localizedFallbackValueClass = $localizedFallbackValueClass;
        $this->localeClass = $localeClass;

        $this->value = new $localizedFallbackValueClass;
        $this->locale = new $localeClass;
    }

    /** {@inheritdoc} */
    public function normalize($object, $format = null, array $context = [])
    {
        $result = [];

        foreach ($object as $item) {
            $result[LocaleCodeFormatter::formatName($item->getLocale())] = [
                'fallback' => $item->getFallback(),
                'string' => $item->getString(),
                'text' => $item->getText(),
            ];
        }

        return $result;
    }

    /** {@inheritdoc} */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!is_array($data)) {
            return new ArrayCollection();
        }
        $itemType = $this->getItemType($class);
        if (!$itemType) {
            return new ArrayCollection($data);
        }
        $result = new ArrayCollection();
        foreach ($data as $localeCode => $item) {
            /** @var LocalizedFallbackValue $object */
            $object = clone $this->value;

            if ($localeCode !== LocaleCodeFormatter::DEFAULT_LOCALE) {
                if (!array_key_exists($localeCode, $this->locales)) {
                    $this->locales[$localeCode] = clone $this->locale;
                    $this->locales[$localeCode]->setCode($localeCode);
                }
                $object->setLocale($this->locales[$localeCode]);
            }

            if (array_key_exists('fallback', $item)) {
                $object->setFallback((string)$item['fallback']);
            }
            if (array_key_exists('text', $item)) {
                $object->setText((string)$item['text']);
            }
            if (array_key_exists('string', $item)) {
                $object->setString((string)$item['string']);
            }

            $result->set($localeCode, $object);
        }

        return $result;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        if (!parent::supportsNormalization($data, $format, $context)) {
            return false;
        }

        return $this->isApplicable($context);
    }

    /** {@inheritdoc} */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
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
