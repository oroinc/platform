<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Oro\Bundle\ActionBundle\Model\ParameterInterface;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Component\PhpUtils\Exception\UnsafeUnserializationException;
use Oro\Component\PhpUtils\PhpUnserializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Normalizes and denormalizes standard workflow attribute types.
 *
 * This normalizer handles conversion of scalar and object attribute values between
 * their model representation and serializable form.
 */
class StandardAttributeNormalizer implements AttributeNormalizer
{
    protected $normalTypes = [
        'string'  => 'string',
        'int'     => 'integer',
        'integer' => 'integer',
        'bool'    => 'boolean',
        'boolean' => 'boolean',
        'float'   => 'float',
        'array'   => 'array',
        'object'  => 'object',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private PhpUnserializerInterface $unserializer
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(Workflow $workflow, ParameterInterface $attribute, $attributeValue)
    {
        $normalType = $this->normalTypes[$attribute->getType()];
        $normalizeMethod = 'normalize' . ucfirst($normalType);
        return (null === $attributeValue) ? $attributeValue : $this->$normalizeMethod($attributeValue, $attribute);
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    protected function normalizeString($value)
    {
        if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            return (string) $value;
        }
        return null;
    }

    /**
     * @param mixed $value
     * @return int|null
     */
    protected function normalizeInteger($value)
    {
        return is_scalar($value) ? (int)$value : null;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function normalizeBoolean($value)
    {
        return (bool)$value;
    }

    /**
     * @param mixed $value
     * @return float|null
     */
    protected function normalizeFloat($value)
    {
        return is_scalar($value) ? (float)$value : null;
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function normalizeArray($value)
    {
        if (!is_array($value)) {
            $value = [];
        }
        return $this->serialize($value);
    }

    /**
     * @param mixed $value
     * @param ParameterInterface $attribute
     * @return string
     */
    protected function normalizeObject($value, ParameterInterface $attribute)
    {
        $class = $attribute->getOption('class');
        if (!is_object($value) || !$value instanceof $class) {
            return null;
        }
        return $this->serialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(Workflow $workflow, ParameterInterface $attribute, $attributeValue)
    {
        $normalType = $this->normalTypes[$attribute->getType()];
        $denormalizeMethod = 'denormalize' . ucfirst($normalType);
        return (null === $attributeValue) ? $attributeValue : $this->$denormalizeMethod($attributeValue, $attribute);
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    protected function denormalizeString($value)
    {
        return is_scalar($value) ? (string)$value : null;
    }

    /**
     * @param mixed $value
     * @return int|null
     */
    protected function denormalizeInteger($value)
    {
        return is_scalar($value) ? (int)$value : null;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function denormalizeBoolean($value)
    {
        return (bool)$value;
    }

    /**
     * @param mixed $value
     * @return float|null
     */
    protected function denormalizeFloat($value)
    {
        return is_scalar($value) ? (float)$value : null;
    }

    /**
     * @param mixed $value
     * @param ParameterInterface $attribute
     * @return array
     */
    protected function denormalizeArray($value, ParameterInterface $attribute)
    {
        if (!is_string($value)) {
            return [];
        }

        // For arrays allow only scalar values, to be converted to json in the future
        $value = $this->unserialize($value, ['allowed_classes' => false], $attribute);

        if (!is_array($value)) {
            return [];
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param ParameterInterface $attribute
     * @return object|null
     */
    protected function denormalizeObject($value, ParameterInterface $attribute)
    {
        $class = $attribute->getOption('class');
        if (empty($class) || !class_exists($class)) {
            return null;
        }

        /**
         * It's not possible to restrict unserialize to attribute class because it may be of a complex type
         * with relations to other classes.
         */
        $value = $this->unserialize($value, [], $attribute);

        if (!is_object($value) || !$value instanceof $class) {
            $value = null;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(Workflow $workflow, ParameterInterface $attribute, $attributeValue)
    {
        return !empty($this->normalTypes[$attribute->getType()]);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization(Workflow $workflow, ParameterInterface $attribute, $attributeValue)
    {
        return !empty($this->normalTypes[$attribute->getType()]);
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function serialize($value)
    {
        return base64_encode(serialize($value));
    }

    /**
     * @param mixed $value
     * @param array $options
     * @param ParameterInterface|null $attribute
     * @return mixed|null
     */
    protected function unserialize($value, array $options = [], ?ParameterInterface $attribute = null)
    {
        if (!is_string($value)) {
            return null;
        }

        $value = base64_decode($value, true);
        if (!is_string($value) || !$value) {
            $this->logger->error(
                'Failed to base64 decode workflow attribute value',
                [
                    'attribute' => $attribute,
                    'value' => $value
                ]
            );

            return null;
        }

        try {
            $configuredAllowedClass = $attribute?->getOption('class');
            if ($configuredAllowedClass) {
                $options[PhpUnserializerInterface::WHITELIST_CLASSES_KEY] = [$configuredAllowedClass];
            }

            return $this->unserializer->unserialize($value, $options);
        } catch (UnsafeUnserializationException $e) {
            $this->logger->critical(
                'Failed to unserialize workflow attribute value',
                [
                    'exception' => $e,
                    'attribute' => $attribute
                ]
            );

            return null;
        }
    }
}
