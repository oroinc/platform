<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Oro\Bundle\ActionBundle\Model\ParameterInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

class WorkflowDataNormalizer extends SerializerAwareNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @var AttributeNormalizer[]
     */
    protected $attributeNormalizers;

    /**
     * @param AttributeNormalizer $attributeNormalizer
     */
    public function addAttributeNormalizer(AttributeNormalizer $attributeNormalizer)
    {
        $this->attributeNormalizers[] = $attributeNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $attributes = array();
        $workflow = $this->getWorkflow();

        $workflowConfig = $workflow->getDefinition()->getConfiguration();
        $variableNames = $this->getVariablesNamesFromConfiguration($workflowConfig);

        foreach ($object as $attributeName => $attributeValue) {
            // skip variable serialization
            if (in_array($attributeName, $variableNames, true)) {
                continue;
            }

            $attribute = $this->getAttribute($workflow, $attributeName);
            $attributeValue = $this->normalizeAttribute($workflow, $attribute, $attributeValue);

            if (null !== $attributeValue &&
                !is_scalar($attributeValue) &&
                $this->serializer instanceof NormalizerInterface
            ) {
                $attributeValue = $this->serializer->normalize($attributeValue, $format);
            }
            $attributes[$attributeName] = $attributeValue;
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $denormalizedData = array();
        $workflow = $this->getWorkflow();

        foreach ($data as $attributeName => $attributeValue) {
            // Skip attributes that already removed from configuration, they will be cleaned after next data update.
            if ($this->hasAttribute($workflow, $attributeName)) {
                $attribute                        = $this->getAttribute($workflow, $attributeName);
                $attributeValue                   = $this->denormalizeAttribute($workflow, $attribute, $attributeValue);
                $denormalizedData[$attributeName] = $attributeValue;
            }
        }

        /** @var WorkflowData $object */
        $object = new $class($denormalizedData);
        $object->setFieldsMapping($workflow->getAttributesMapping());

        // populate WorkflowData with variables
        if ($variables = $workflow->getVariables()) {
            foreach ($variables as $name => $variable) {
                $object->set($name, $variable->getValue());
            }
        }

        return $object;
    }

    /**
     * @param Workflow $workflow
     * @param          $attributeName
     *
     * @return bool
     */
    protected function hasAttribute(Workflow $workflow, $attributeName)
    {
        return (bool)$workflow->getAttributeManager()->getAttribute($attributeName);
    }

    /**
     * @param Workflow $workflow
     * @param string $attributeName
     * @return \Oro\Bundle\ActionBundle\Model\Attribute
     * @throws SerializerException If attribute not found
     */
    protected function getAttribute(Workflow $workflow, $attributeName)
    {
        $attribute = $workflow->getAttributeManager()->getAttribute($attributeName);
        if (!$attribute) {
            throw new SerializerException(
                sprintf(
                    'Workflow "%s" has no attribute "%s"',
                    $workflow->getName(),
                    $attributeName
                )
            );
        }
        return $attribute;
    }

    /**
     * @param Workflow $workflow
     * @param ParameterInterface $attribute
     * @param mixed $attributeValue
     * @return mixed
     */
    protected function normalizeAttribute(Workflow $workflow, ParameterInterface $attribute, $attributeValue)
    {
        $normalizer = $this->findAttributeNormalizer('normalization', $workflow, $attribute, $attributeValue);

        return $normalizer->normalize($workflow, $attribute, $attributeValue);
    }

    /**
     * @param Workflow $workflow
     * @param ParameterInterface $attribute
     * @param mixed $attributeValue
     * @return AttributeNormalizer
     * @throws SerializerException
     */
    protected function denormalizeAttribute(Workflow $workflow, ParameterInterface $attribute, $attributeValue)
    {
        $normalizer = $this->findAttributeNormalizer('denormalization', $workflow, $attribute, $attributeValue);

        return $normalizer->denormalize($workflow, $attribute, $attributeValue);
    }

    /**
     * @param string $direction
     * @param Workflow $workflow
     * @param ParameterInterface $attribute
     * @param mixed $attributeValue
     * @return AttributeNormalizer
     * @throws SerializerException
     */
    protected function findAttributeNormalizer(
        $direction,
        Workflow $workflow,
        ParameterInterface $attribute,
        $attributeValue
    ) {
        $method = 'supports' . ucfirst($direction);
        foreach ($this->attributeNormalizers as $normalizer) {
            if ($normalizer->$method($workflow, $attribute, $attributeValue)) {
                return $normalizer;
            }
        }
        throw new SerializerException(
            sprintf(
                'Cannot handle "%s" of attribute "%s" of workflow "%s"',
                $direction,
                $attribute->getName(),
                $workflow->getName()
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        return is_object($data) && $this->supportsClass(get_class($data));
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = array())
    {
        return $this->supportsClass($type);
    }

    /**
     * Checks if the given class is WorkflowData or it's ancestor.
     *
     * @param string $class
     * @return Boolean
     */
    protected function supportsClass($class)
    {
        $workflowDataClass = 'Oro\Bundle\WorkflowBundle\Model\WorkflowData';
        return $workflowDataClass == $class
            || (is_string($class) && class_exists($class) && in_array($workflowDataClass, class_parents($class)));
    }

    /**
     * Get Workflow
     *
     * @return Workflow
     * @throws SerializerException
     */
    protected function getWorkflow()
    {
        if (!$this->serializer instanceof WorkflowAwareSerializer) {
            throw new SerializerException(
                sprintf(
                    'Cannot get Workflow. Serializer must implement %s',
                    'Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer'
                )
            );
        }
        return $this->serializer->getWorkflow();
    }

    /**
     * @param array $configuration
     *
     * @return array
     */
    protected function getVariablesNamesFromConfiguration(array $configuration)
    {
        $definitionsNode = WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS;
        $variablesNode   = WorkflowConfiguration::NODE_VARIABLES;

        if (!isset($configuration[$definitionsNode][$variablesNode])) {
            return [];
        }

        return array_keys($configuration[$definitionsNode][$variablesNode]);
    }
}
