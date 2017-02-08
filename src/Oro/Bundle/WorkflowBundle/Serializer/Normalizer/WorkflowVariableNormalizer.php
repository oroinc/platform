<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Oro\Bundle\ActionBundle\Model\ParameterInterface;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class WorkflowVariableNormalizer extends WorkflowDataNormalizer
{
    /**
     * @param Workflow $workflow
     * @param ParameterInterface $variable
     * @param mixed $variableValue
     *
     * @return mixed
     */
    public function normalizeVariable(Workflow $workflow, ParameterInterface $variable, $variableValue)
    {
        return $this->normalizeAttribute($workflow, $variable, $variableValue);
    }

    /**
     * @param Workflow $workflow
     * @param ParameterInterface $variable
     * @param mixed $variableValue
     *
     * @return AttributeNormalizer
     *
     * @throws SerializerException
     */
    public function denormalizeVariable(Workflow $workflow, ParameterInterface $variable, $variableValue)
    {
        // configuration is serialized with variable configuration
        if ('array' === $variable->getType()) {
            return $variableValue;
        }

        return $this->denormalizeAttribute($workflow, $variable, $variableValue);
    }
}
