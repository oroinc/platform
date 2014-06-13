<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

class ProcessDataNormalizer extends SerializerAwareNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $denormalizedData = $this->denormalizeValues($data);

        return new $class($denormalizedData);
    }

    /**
     * Returns a completed entity
     *
     * @param string $className
     * @param array $attributes
     * @return object
     */
    protected function denormalizeEntity($className, $attributes = array())
    {
        $reflection = new \ReflectionClass($className);
        $entity     = $reflection->newInstanceWithoutConstructor();

        foreach ($attributes as $name => $value) {
            $this->writeAttribute($entity, $name, $value);
        }

        return $entity;
    }

    /**
     * @param array $values
     * @return array denormalized data
     */
    protected function denormalizeValues(array $values)
    {
        $denormalizedData = array();

        foreach ($values as $key => $value) {
            if (empty($value)) {
                $denormalizedData[$key] = null;
            } elseif (!empty($value['className'])) {
                $className = $value['className'];
                unset($value['className']);
                $denormalizedData[$key] = $this->denormalizeEntity($className, $value);
            } elseif (is_array($value)) {
                foreach ($value as $attributeName => $attributeValue) {
                    if (is_array($attributeValue) || is_object($attributeValue)) {
                        $denormalizedData[$key][$attributeName] = $this->denormalizeValues($value);
                    } else {
                        $denormalizedData[$key][$attributeName] = $attributeValue;
                    }
                }
            } else {
                $denormalizedData[$key] = $value;
            }
        }

        return $denormalizedData;
    }

    /**
     * {@inheritdoc}
     * @param object|array $object
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $normalizedData = array();

        foreach ($object as $key => $value) {
            if (empty($value)) {
                $normalizedData[$key] = null;
            } elseif (is_object($value)) {
                $normalizedData[$key] = $this->normalizeEntity($value, $format);
            } elseif (is_array($value)) {
                foreach ($value as $attributeName => $attributeValue) {
                    if (is_array($attributeValue)) {
                        $normalizedData[$key][$attributeName] = $this->normalize($attributeValue, $format, $context);
                    } elseif (is_object($attributeValue)) {
                        $normalizedData[$key][$attributeName] = null;
                    } else {
                        $normalizedData[$key][$attributeName] = $attributeValue;
                    }
                }
            } else {
                $normalizedData[$key] = $value;
            }
        }

        return $normalizedData;
    }

    protected function normalizeEntity($entity, $format = null)
    {
        $normalizedData['className'] = get_class($entity);
        $reflection = new \ReflectionClass($entity);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $name      = $property->getName();
            $attribute = $this->readAttribute($entity, $name);
            if ($attribute instanceof \DateTime) {
                $attribute = $this->serializer->serialize($attribute, $format);
            }
            $normalizedData[$name] = is_object($attribute) ? null : $attribute;
        }

        return $normalizedData;
    }

    /**
     * @param $class
     * @param $attributeName
     * @return mixed
     */
    private function readAttribute($class, $attributeName)
    {
        $reflection = new \ReflectionProperty($class, $attributeName);
        $reflection->setAccessible(true);
        return $reflection->getValue($class);
    }

    /**
     * Checks if the given class is ProcessData or it's ancestor.
     *
     * @param string $class
     * @return boolean
     */
    protected function supportsClass($class)
    {
        $workflowDataClass = 'Oro\Bundle\WorkflowBundle\Model\ProcessData';
        return '\DateTime' == $class || 'DateTime' == $class ||
               $workflowDataClass == $class ||
               (is_string($class) && class_exists($class) && in_array($workflowDataClass, class_parents($class)));
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->supportsClass($type);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $this->supportsClass(get_class($data));
    }

    /**
     * @param object $class
     * @param string $attributeName
     * @param mixed $attributeValue
     */
    private function writeAttribute($class, $attributeName, $attributeValue)
    {
        $reflectionProperty = new \ReflectionProperty($class, $attributeName);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($class, $attributeValue);
        $reflectionProperty->setAccessible(false);
    }
}
