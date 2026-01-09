<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Oro\Bundle\ActionBundle\Model\ParameterInterface;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

/**
 * Defines the contract for normalizing and denormalizing workflow attribute values.
 *
 * Implementations handle conversion of attribute values between their model representation
 * and scalar form for serialization and storage.
 */
interface AttributeNormalizer
{
    /**
     * Normalizes a value of attribute into a scalar
     *
     * @param Workflow $workflow
     * @param ParameterInterface $attribute
     * @param mixed $attributeValue
     * @throws WorkflowException
     * @return mixed
     */
    public function normalize(Workflow $workflow, ParameterInterface $attribute, $attributeValue);

    /**
     * Denormalizes value of attribute back into it's model representation
     *
     * @param Workflow $workflow
     * @param ParameterInterface $attribute
     * @param mixed $attributeValue
     * @return mixed
     */
    public function denormalize(Workflow $workflow, ParameterInterface $attribute, $attributeValue);

    /**
     * Supports normalization of attribute
     *
     * @param Workflow $workflow
     * @param ParameterInterface $attribute
     * @param mixed $attributeValue
     * @return bool
     */
    public function supportsNormalization(Workflow $workflow, ParameterInterface $attribute, $attributeValue);

    /**
     * Supports denormalization of attribute
     *
     * @param Workflow $workflow
     * @param ParameterInterface $attribute
     * @param mixed $attributeValue
     * @return bool
     */
    public function supportsDenormalization(Workflow $workflow, ParameterInterface $attribute, $attributeValue);
}
