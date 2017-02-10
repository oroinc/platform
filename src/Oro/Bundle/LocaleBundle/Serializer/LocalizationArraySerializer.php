<?php

namespace Oro\Bundle\LocaleBundle\Serializer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;

use Symfony\Component\Serializer\SerializerInterface;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizationArraySerializer implements SerializerInterface
{
    protected static $fieldsToSerializeInLocalization = [
        'id',
        'languageCode',
        'formattingCode',
        'name',
        'createdAt',
        'updatedAt',
        'updatedAtSet'
    ];

    protected static $fieldsToSerializeInLocalizedFallbackValue = [
        'id',
        'fallback',
        'string',
        'text'
    ];

    protected static $titleKeyName = 'titles';

    /**
     * {@inheritdoc}
     * Available format: array
     * @return array
     */
    public function serialize($data, $format = 'array', array $context = array())
    {
        if ($format !== 'array') {
            throw new \InvalidArgumentException("Only 'array' format is available.");
        }
        if (!is_object($data) || !$data instanceof Localization) {
            throw new \InvalidArgumentException(
                sprintf("First argument must be object of type '%s'", Localization::class)
            );
        }
        if ($data->getTitles() instanceof PersistentCollection) {
            $data->getTitles()->initialize();
        }

        $localizationArray = [];
        $reflectionLocalization = new \ReflectionObject($data);

        foreach (static::$fieldsToSerializeInLocalization as $propertyName) {
            $reflectionProperty = $reflectionLocalization->getProperty($propertyName);
            $reflectionProperty->setAccessible(true);
            $localizationArray[$reflectionProperty->getName()] = $reflectionProperty->getValue($data);
            $reflectionProperty->setAccessible(false);
        }

        $titlesArray = $this->getSerializedTitles($data->getTitles());
        $localizationArray[static::$titleKeyName] = $titlesArray;

        return $localizationArray;
    }

    /**
     * {@inheritdoc}
     * Available type: Localization
     * Available format: array
     * @return Localization
     */
    public function deserialize($data, $type = Localization::class, $format = 'array', array $context = array())
    {
        if ($format !== 'array' || !is_array($data)) {
            throw new \InvalidArgumentException("Only 'array' format is available.");
        }
        if ($type !== Localization::class) {
            throw new \InvalidArgumentException(
                sprintf("Only '%s' type is available", Localization::class)
            );
        }
        $localization = new Localization();
        $reflectionLocalization = new \ReflectionObject($localization);

        foreach (static::$fieldsToSerializeInLocalization as $propertyName) {
            $reflectionProperty = $reflectionLocalization->getProperty($propertyName);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($localization, $data[$reflectionProperty->getName()]);
            $reflectionProperty->setAccessible(false);
        }

        $titlesCollection = $this->getDeserializedTitles($data[static::$titleKeyName]);
        $reflectionLocalization = new \ReflectionObject($localization);
        $titlesProperty = $reflectionLocalization->getProperty(static::$titleKeyName);
        $titlesProperty->setAccessible(true);
        $titlesProperty->setValue($localization, $titlesCollection);
        $titlesProperty->setAccessible(false);

        return $localization;
    }

    /**
     * @param Collection $titles
     * @return array
     */
    private function getSerializedTitles(Collection $titles)
    {
        $titlesArray = [];
        if (!empty($titles)) {
            foreach ($titles as $key => $title) {
                $reflectionTitle = new \ReflectionObject($title);
                foreach (static::$fieldsToSerializeInLocalizedFallbackValue as $propertyName) {
                    $reflectionProperty = $reflectionTitle->getProperty($propertyName);
                    $reflectionProperty->setAccessible(true);
                    $titlesArray[$key][$reflectionProperty->getName()] = $reflectionProperty->getValue($title);
                    $reflectionProperty->setAccessible(false);
                }
            }
        }

        return $titlesArray;
    }

    /**
     * @param array $titlesArray
     * @return ArrayCollection
     */
    private function getDeserializedTitles(array $titlesArray)
    {
        $titlesCollection = new ArrayCollection();
        if (!empty($titlesArray)) {
            foreach ($titlesArray as $titleArray) {
                $title = new LocalizedFallbackValue();
                $reflectionTitle = new \ReflectionObject($title);
                foreach (static::$fieldsToSerializeInLocalizedFallbackValue as $propertyName) {
                    $reflectionProperty = $reflectionTitle->getProperty($propertyName);
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($title, $titleArray[$propertyName]);
                    $reflectionProperty->setAccessible(false);
                }
                $titlesCollection->add($title);
            }
        }
        return $titlesCollection;
    }
}
