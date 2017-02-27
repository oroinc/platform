<?php

namespace Oro\Bundle\LocaleBundle\Serializer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\SerializerInterface;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizationArraySerializer implements SerializerInterface
{
    /**
     * @var array
     */
    protected static $fieldsToSerializeInLocalization = [
        'id',
        'languageCode',
        'formattingCode',
        'name',
        'createdAt',
        'updatedAt',
        'updatedAtSet'
    ];

    /**
     * @var array
     */
    protected static $fieldsToSerializeInLocalizedFallbackValue = [
        'id',
        'fallback',
        'string',
        'text'
    ];

    /**
     * Used to deserialize relation properties
     * Format: [propertyName => [relationClassName, relationPropertyName]]
     *
     * @var array
     */
    protected static $relationClasses = [
        'parentLocalization' => [Localization::class, 'id']
    ];

    /**
     * @var string
     */
    protected static $titleKeyName = 'titles';

    /**
     * {@inheritdoc}
     * Available format: array
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function serialize($data, $format = 'array', array $context = [])
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

        $localizationArray = $this->serializeRelationProperties($data, $reflectionLocalization, $localizationArray);

        $titlesArray = $this->getSerializedTitles($data->getTitles());
        $localizationArray[static::$titleKeyName] = $titlesArray;

        return $localizationArray;
    }

    /**
     * {@inheritdoc}
     * Available type: Localization
     * Available format: array
     * @throws \InvalidArgumentException
     *
     * @return Localization
     */
    public function deserialize($data, $type = Localization::class, $format = 'array', array $context = [])
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

            if (!array_key_exists($reflectionProperty->getName(), $data)) {
                continue;
            }

            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($localization, $data[$reflectionProperty->getName()]);
            $reflectionProperty->setAccessible(false);
        }

        $this->deserializeRelationProperties($data, $reflectionLocalization, $localization);
        $this->deserializeTitles($data, $localization);

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

    /**
     * @param Localization      $localization
     * @param \ReflectionObject $reflectionLocalization
     * @param array             $localizationArray
     * @return array
     */
    protected function serializeRelationProperties(
        Localization $localization,
        \ReflectionObject $reflectionLocalization,
        array $localizationArray
    ) {
        foreach (static::$relationClasses as $propertyName => $classOptions) {
            $classPropertyName = $classOptions[1];

            $reflectionProperty = $reflectionLocalization->getProperty($propertyName);
            $reflectionProperty->setAccessible(true);

            if ($reflectionProperty->getValue($localization) === null) {
                $propertyValue = null;
            } else {
                try {
                    $propertyValue = (new PropertyAccessor())->getValue(
                        $reflectionProperty->getValue($localization),
                        $classPropertyName
                    );
                } catch (\Exception $exception) {
                    $propertyValue = null;
                }
            }
            $localizationArray[$reflectionProperty->getName()] = $propertyValue;
            $reflectionProperty->setAccessible(false);
        }

        return $localizationArray;
    }

    /**
     * @param array             $data
     * @param \ReflectionObject $reflectionLocalization
     * @param Localization      $localization
     */
    protected function deserializeRelationProperties(
        array $data,
        \ReflectionObject $reflectionLocalization,
        Localization $localization
    ) {
        foreach (static::$relationClasses as $propertyName => list($className, $classPropertyName)) {
            if (!array_key_exists($propertyName, $data) || $data[$propertyName] === null) {
                continue;
            }

            $reflectionProperty = $reflectionLocalization->getProperty($propertyName);

            $relationClass = new $className();
            $relationReflectionClass = new \ReflectionObject($relationClass);
            $relationReflectionProperty = $relationReflectionClass->getProperty($classPropertyName);
            $relationReflectionProperty->setAccessible(true);
            $relationReflectionProperty->setValue($relationClass, $data[$propertyName]);
            $relationReflectionProperty->setAccessible(false);

            $reflectionProperty->setAccessible(true);

            $reflectionProperty->setValue($localization, $relationClass);
            $reflectionProperty->setAccessible(false);
        }
    }

    /**
     * @param array        $data
     * @param Localization $localization
     */
    private function deserializeTitles($data, Localization $localization)
    {
        if (!array_key_exists(static::$titleKeyName, $data)) {
            return;
        }

        $titlesCollection = $this->getDeserializedTitles($data[static::$titleKeyName]);
        $reflectionLocalization = new \ReflectionObject($localization);
        $titlesProperty = $reflectionLocalization->getProperty(static::$titleKeyName);
        $titlesProperty->setAccessible(true);
        $titlesProperty->setValue($localization, $titlesCollection);
        $titlesProperty->setAccessible(false);
    }
}
