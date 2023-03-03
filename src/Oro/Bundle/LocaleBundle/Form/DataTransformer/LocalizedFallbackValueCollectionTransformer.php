<?php

namespace Oro\Bundle\LocaleBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Transforms entity representation of localized fallback value collection to array representation like
 * [
 *     'values' => [
 *         null => <value>,
 *         1 => <value>,
 *         2 => new FallbackType(FallbackType::SYSTEM),
 *     ],
 *     'ids' => [
 *         0 => 1,
 *         1 => 2,
 *         2 => 3,
 *     ],
 * ]
 * for processing in form, and vice versa.
 */
class LocalizedFallbackValueCollectionTransformer implements DataTransformerInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string|array
     */
    protected $field;

    /**
     * @var string
     */
    protected $valueClass;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param ManagerRegistry $registry
     * @param string|array $field
     * @param string $valueClass
     */
    public function __construct(ManagerRegistry $registry, $field, $valueClass)
    {
        $this->registry = $registry;
        $this->field = $field;
        $this->valueClass = $valueClass;

        if (!is_a($valueClass, AbstractLocalizedFallbackValue::class, true)) {
            throw new InvalidArgumentException('Value class must extend AbstractLocalizedFallbackValue');
        }
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
            /* @var AbstractLocalizedFallbackValue $localizedFallbackValue */
            $localization = $localizedFallbackValue->getLocalization();
            if ($localization) {
                $key = $localization->getId();
            } else {
                $key = 0;
            }

            $result[LocalizedFallbackValueCollectionType::FIELD_VALUES][$key ?: null]
                = $this->getResultValue($localizedFallbackValue);

            if ($localizedFallbackValue->getId()) {
                $result[LocalizedFallbackValueCollectionType::FIELD_IDS][$key] = $localizedFallbackValue->getId();
            }
        }

        return $result;
    }

    /**
     * @param AbstractLocalizedFallbackValue $localizedFallbackValue
     *
     * @return string|FallbackType
     */
    private function getResultValue(AbstractLocalizedFallbackValue $localizedFallbackValue)
    {
        $localization = $localizedFallbackValue->getLocalization();
        $propertyAccessor = $this->getPropertyAccessor();
        $fallback = $localizedFallbackValue->getFallback();

        if ($fallback && $localization) {
            $value = new FallbackType($fallback);
        } elseif (\is_array($this->field)) {
            $value = [];
            foreach ($this->field as $field) {
                $value[$field] = $propertyAccessor->getValue($localizedFallbackValue, $field);
            }
        } else {
            $value = $propertyAccessor->getValue($localizedFallbackValue, $this->field);
        }

        return $value;
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
     * @return AbstractLocalizedFallbackValue
     */
    protected function generateLocalizedFallbackValue($entityId, $localizationId, $fieldValue)
    {
        $localizedFallbackValue = null;
        if ($entityId) {
            $localizedFallbackValue = $this->findLocalizedFallbackValue($entityId);
        }
        if (!$localizedFallbackValue) {
            $localizedFallbackValue = new $this->valueClass();
        }
        $localizedFallbackValue->setLocalization($localizationId ? $this->findLocalization($localizationId) : null);

        $propertyAccessor = $this->getPropertyAccessor();

        if ($fieldValue instanceof FallbackType) {
            $localizedFallbackValue->setFallback($fieldValue->getType());

            foreach ((array) $this->field as $field) {
                $propertyAccessor->setValue($localizedFallbackValue, $field, null);
            }
        } elseif (\is_array($this->field)) {
            $localizedFallbackValue->setFallback(null);
            foreach ($this->field as $field) {
                $propertyAccessor->setValue($localizedFallbackValue, $field, $fieldValue[$field] ?? null);
            }
        } else {
            $localizedFallbackValue->setFallback(null);
            $propertyAccessor->setValue($localizedFallbackValue, $this->field, $fieldValue);
        }

        return $localizedFallbackValue;
    }

    /**
     * @param int $id
     * @return AbstractLocalizedFallbackValue|null
     */
    protected function findLocalizedFallbackValue($id)
    {
        return $this->registry->getRepository($this->valueClass)->find($id);
    }

    /**
     * @param int $id
     * @return Localization
     */
    protected function findLocalization($id)
    {
        $localization = $this->registry->getRepository(Localization::class)->find($id);

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
