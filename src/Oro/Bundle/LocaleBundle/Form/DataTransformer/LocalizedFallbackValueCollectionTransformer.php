<?php

namespace Oro\Bundle\LocaleBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizedFallbackValueCollectionTransformer implements DataTransformerInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $field;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param ManagerRegistry $registry
     * @param string $field
     */
    public function __construct(ManagerRegistry $registry, $field)
    {
        $this->registry = $registry;
        $this->field = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        if (!is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'array or Traversable');
        }

        $result = [
            LocalizedFallbackValueCollectionType::FIELD_VALUES => [],
            LocalizedFallbackValueCollectionType::FIELD_IDS => [],
        ];

        foreach ($value as $localizedFallbackValue) {
            /* @var $localizedFallbackValue LocalizedFallbackValue */
            $localization = $localizedFallbackValue->getLocalization();
            if ($localization) {
                $key = $localization->getId();
            } else {
                $key = 0;
            }

            $fallback = $localizedFallbackValue->getFallback();
            if ($fallback) {
                $value = new FallbackType($fallback);
            } else {
                $value = $this->getPropertyAccessor()->getValue($localizedFallbackValue, $this->field);
            }

            $result[LocalizedFallbackValueCollectionType::FIELD_VALUES][$key ?: null] = $value;
            $result[LocalizedFallbackValueCollectionType::FIELD_IDS][$key] = $localizedFallbackValue->getId();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }

        if (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        $fieldValues = [];
        if (isset($value[LocalizedFallbackValueCollectionType::FIELD_VALUES])) {
            $fieldValues = $value[LocalizedFallbackValueCollectionType::FIELD_VALUES];
        }

        $fieldIds = [];
        if (isset($value[LocalizedFallbackValueCollectionType::FIELD_IDS])) {
            $fieldIds = $value[LocalizedFallbackValueCollectionType::FIELD_IDS];
        }

        $result = new ArrayCollection();

        foreach ($fieldValues as $localizationId => $fieldValue) {
            if (!$localizationId) {
                $localizationId = 0;
            }

            $entityId = null;
            if (!empty($fieldIds[$localizationId])) {
                $entityId = $fieldIds[$localizationId];
            }

            $result->add($this->generateLocalizedFallbackValue($entityId, $localizationId, $fieldValue));
        }

        return $result;
    }

    /**
     * @param int|null $entityId
     * @param int|null $localizationId
     * @param string|FallbackType $fieldValue
     * @return LocalizedFallbackValue
     */
    protected function generateLocalizedFallbackValue($entityId, $localizationId, $fieldValue)
    {
        $localizedFallbackValue = null;
        if ($entityId) {
            $localizedFallbackValue = $this->findLocalizedFallbackValue($entityId);
        }
        if (!$localizedFallbackValue) {
            $localizedFallbackValue = new LocalizedFallbackValue();
        }
        $localizedFallbackValue->setLocalization($localizationId ? $this->findLocalization($localizationId) : null);

        if ($fieldValue instanceof FallbackType) {
            $localizedFallbackValue->setFallback($fieldValue->getType());
            $this->getPropertyAccessor()->setValue($localizedFallbackValue, $this->field, null);
        } else {
            $localizedFallbackValue->setFallback(null);
            $this->getPropertyAccessor()->setValue($localizedFallbackValue, $this->field, $fieldValue);
        }

        return $localizedFallbackValue;
    }

    /**
     * @param int $id
     * @return LocalizedFallbackValue|null
     */
    protected function findLocalizedFallbackValue($id)
    {
        return $this->registry->getRepository('OroLocaleBundle:LocalizedFallbackValue')->find($id);
    }

    /**
     * @param int $id
     * @return Localization
     */
    protected function findLocalization($id)
    {
        $localization = $this->registry->getRepository('OroLocaleBundle:Localization')->find($id);

        if (!$localization) {
            throw new TransformationFailedException(sprintf('Undefined localization with ID=%s', $id));
        }

        return $localization;
    }

    /**
     * @return PropertyAccessor
     */
    public function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
