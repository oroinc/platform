<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Wraps service method as an action_group.
 * Responsible for execution, parameters resolving and result mapping.
 */
class ActionGroupServiceAdapter implements ActionGroupInterface
{
    private ?array $parameters = null;
    private array $parameterNameToArgumentName = [];
    private array $argumentNameToParameterName = [];
    private ?ActionGroupDefinition $definition = null;

    public function __construct(
        private ActionGroup\ParametersResolver $parametersResolver,
        private object $service,
        private string $method,
        private ?string $returnValueName,
        private ?array $parametersConfig
    ) {
    }

    public function execute(ActionData $data, Collection $errors = null): ActionData
    {
        try {
            $this->parametersResolver->resolve($data, $this, $errors, true);

            // call_user_func_array allows to use named arguments
            $result = call_user_func_array(
                [$this->service, $this->method],
                $this->getMethodArguments($data)
            );

            if ($this->returnValueName) {
                $result = [$this->returnValueName => $result];
            }

            if (is_array($result)) {
                $this->mapResultToContext($data, $result);
            }
        } catch (ActionGroup\Exception $e) {
            $this->processException($errors, $e);
        }

        return $data;
    }

    public function getDefinition(): ActionGroupDefinition
    {
        if (!$this->definition) {
            $this->definition = new ActionGroupDefinition();
            $this->definition->setName('service:' . get_class($this->service) . '::' . $this->method);
        }

        return $this->definition;
    }

    public function isAllowed(ActionData $data, Collection $errors = null): bool
    {
        return true;
    }

    public function getParameters(): array
    {
        if ($this->parameters === null) {
            $this->fillParameterArgumentMapping();

            $this->parameters = [];
            $reflection = new \ReflectionMethod($this->service, $this->method);
            foreach ($reflection->getParameters() as $methodParameter) {
                $argName = $methodParameter->getName();
                $parameterName = $this->argumentNameToParameterName[$argName] ?? $argName;

                $parameter = new Parameter($parameterName);
                if ($methodParameter->hasType()) {
                    $parameter->setType($methodParameter->getType()->getName());
                }

                if ($methodParameter->isOptional() && $methodParameter->isDefaultValueAvailable()) {
                    $parameter->setDefault($methodParameter->getDefaultValue());
                }

                $this->parameters[] = $parameter;
            }
        }

        return $this->parameters;
    }

    private function getMethodArguments(ActionData $data): array
    {
        $parameterValues = $this->parametersResolver->getParametersValues($data, $this, true);
        $arguments = [];
        foreach ($parameterValues as $parameterName => $parameterValue) {
            $argName = $this->parameterNameToArgumentName[$parameterName] ?? $parameterName;
            $arguments[$argName] = $parameterValue;
        }

        return $arguments;
    }

    private function mapResultToContext(ActionData $data, array $result): void
    {
        foreach ($result as $key => $value) {
            if ($data->offsetExists($key)) {
                $dataValue = $data->offsetGet($key);
                if (is_array($value) && is_array($dataValue)) {
                    $value = ArrayUtil::arrayMergeRecursiveDistinct($dataValue, $value);
                }
            }

            $data->offsetSet($key, $value);
        }
    }

    private function processException(?Collection $errors, ActionGroup\Exception $e): void
    {
        if (!$errors) {
            return;
        }

        $errors->add(['message' => $e->getMessage()]);
    }

    private function fillParameterArgumentMapping(): void
    {
        if ($this->parametersConfig) {
            foreach ($this->parametersConfig as $name => $parameterConfig) {
                if (empty($parameterConfig['service_argument_name'])) {
                    continue;
                }
                $argName = $parameterConfig['service_argument_name'];
                $this->parameterNameToArgumentName[$name] = $argName;
                $this->argumentNameToParameterName[$argName] = $name;
            }
        }
    }
}
