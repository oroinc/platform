<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\WorkflowVariableNormalizer;

use Oro\Component\Action\Exception\AssemblerException;
use Oro\Component\Action\Model\AbstractAssembler as BaseAbstractAssembler;

class VariableAssembler extends BaseAbstractAssembler
{
    /**
     * @var WorkflowVariableNormalizer
     */
    protected $dataNormalizer;

    /**
     * @param WorkflowVariableNormalizer $dataNormalizer
     */
    public function __construct(WorkflowVariableNormalizer $dataNormalizer)
    {
        $this->dataNormalizer = $dataNormalizer;
    }

    /**
     * @param Workflow $workflow
     * @param array|null $configuration
     *
     * @return Collection
     * @throws AssemblerException If configuration is invalid
     */
    public function assemble(Workflow $workflow, array $configuration = null)
    {
        $variables = new ArrayCollection();
        if (!is_array($configuration)) {
            return $variables;
        }

        $variableDefinitionsConfiguration = $this->getOption(
            $configuration,
            WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS,
            []
        );
        $variablesConfiguration = $this->getOption(
            $variableDefinitionsConfiguration,
            WorkflowConfiguration::NODE_VARIABLES,
            []
        );

        $definitions = $this->parseDefinitions($variablesConfiguration);
        foreach ($definitions as $name => $options) {
            $variable = $this->assembleVariable($workflow, $name, $options);
            $variables->set($name, $variable);
        }

        return $variables;
    }

    /**
     * @param array $configuration
     *
     * @return array
     */
    protected function parseDefinitions(array $configuration)
    {
        $definitions = [];
        foreach ($configuration as $name => $options) {
            if (empty($options)) {
                $options = [];
            }

            $definition = [
                'label'   => $this->getOption($options, 'label'),
                'type'    => $this->getOption($options, 'type'),
                'value'   => $this->getOption($options, 'value'),
                'options' => $this->getOption($options, 'options', []),
            ];

            $definitions[$name] = $definition;
        }

        return $definitions;
    }

    /**
     * @param Workflow $workflow
     * @param string $name
     * @param array $options
     *
     * @return Variable
     */
    protected function assembleVariable(Workflow $workflow, $name, array $options)
    {
        $variable = new Variable();
        $variable
            ->setName($name)
            ->setLabel($options['label'])
            ->setType($options['type'])
            ->setOptions($this->getOption($options, 'options', []));

        $denormalizedValue = $this->dataNormalizer->denormalizeVariable($workflow, $variable, $options['value']);
        $variable->setValue($denormalizedValue);

        $this->validateVariable($variable);

        return $variable;
    }

    /**
     * @param Variable $variable
     * @throws AssemblerException
     */
    protected function validateVariable(Variable $variable)
    {
        $this->assertVariableHasValidType($variable);

        if ('object' === $variable->getType()) {
            $this->assertParameterHasClassOption($variable);
        } else {
            $this->assertParameterHasNoOptions($variable, ['class']);
        }
    }

    /**
     * @param Variable $variable
     * @throws AssemblerException
     */
    protected function assertVariableHasValidType(Variable $variable)
    {
        $type = $variable->getType();
        $allowedTypes = ['bool', 'boolean', 'int', 'integer', 'float', 'string', 'array', 'object'];

        if (!in_array($type, $allowedTypes)) {
            throw new AssemblerException(
                sprintf(
                    'Invalid variable type "%s", allowed types are "%s"',
                    $type,
                    implode('", "', $allowedTypes)
                )
            );
        }
    }
}
