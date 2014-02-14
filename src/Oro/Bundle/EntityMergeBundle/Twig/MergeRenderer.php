<?php

namespace Oro\Bundle\EntityMergeBundle\Twig;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Metadata\Metadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;

class MergeRenderer
{
    /**
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * @var string
     */
    protected $defaultTemplate;

    /**
     * @param \Twig_Environment $environment
     * @param string $defaultTemplate
     */
    public function __construct(\Twig_Environment $environment, $defaultTemplate)
    {
        $this->environment = $environment;
        $this->defaultTemplate = $defaultTemplate;
    }

    /**
     * Render entity label
     *
     * @param mixed $entity
     * @param EntityMetadata $metadata
     * @return string
     */
    public function renderEntityLabel($entity, EntityMetadata $metadata)
    {
        $template = $metadata->get('template') ? : $this->defaultTemplate;

        $stringValue = $this->convertToString($entity, $metadata);

        return $this->environment->render(
            $template,
            array(
                'metadata' => $metadata,
                'entity' => $entity,
                'convertedValue' => $stringValue,
                'isListValue' => false,
            )
        );
    }

    /**
     * Render field value to string
     *
     * @param mixed $value
     * @param FieldMetadata $metadata
     * @param object $entity
     * @return string
     */
    public function renderFieldValue($value, FieldMetadata $metadata, $entity)
    {
        $template = $metadata->get('template') ? : $this->defaultTemplate;

        if (is_array($value) || $value instanceof \Traversable) {
            $isListValue = true;
            $stringValue = $this->convertListToStringArray($value, $metadata);
        } else {
            $isListValue = false;
            $stringValue = $this->convertToString($value, $metadata);
        }

        return $this->environment->render(
            $template,
            array(
                'metadata' => $metadata,
                'value' => $value,
                'convertedValue' => $stringValue,
                'isListValue' => $isListValue,
            )
        );
    }

    /**
     * Convert list to array of strings
     *
     * @param array|\Traversable $list
     * @param Metadata $metadata
     * @return string[]
     * @throws InvalidArgumentException
     */
    protected function convertListToStringArray($list, Metadata $metadata)
    {
        $result = array();

        foreach ($list as $object) {
            $result[] = $this->convertToString($object, $metadata);
        }

        return $result;
    }

    /**
     * Convert value to string
     *
     * @param mixed $value
     * @param Metadata $metadata
     * @return string
     */
    protected function convertToString($value, Metadata $metadata)
    {
        if (null === $value || is_scalar($value)) {
            return (string) $value;
        }

        $method = $metadata->get('cast_method') ? : '__toString';

        if (method_exists($value, $method)) {
            return $value->$method();
        }

        return '';
    }
}
