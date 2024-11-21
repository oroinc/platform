<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Event\ActionGroupEventDispatcher;
use Oro\Bundle\ActionBundle\Event\ActionGroupExecuteEvent;
use Oro\Bundle\ActionBundle\Event\ActionGroupGuardEvent;
use Oro\Bundle\ActionBundle\Event\ActionGroupPreExecuteEvent;
use Oro\Bundle\ActionBundle\Exception\ForbiddenActionGroupException;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Wraps service method as an action_group.
 * Responsible for execution, parameters resolving and result mapping.
 */
class ActionGroupServiceAdapter implements ActionGroupInterface
{
    public const RESULT_VALUE_KEY = '__result__';

    private ?array $parameters = null;
    private array $parameterNameToArgumentName = [];
    private array $argumentNameToParameterName = [];

    public function __construct(
        private ActionGroup\ParametersResolver $parametersResolver,
        private object $service,
        private ActionGroupEventDispatcher $eventDispatcher,
        private string $method,
        private ?string $returnValueName,
        private ?array $parametersConfig,
        private ActionGroupDefinition $definition
    ) {
    }

    #[\Override]
    public function execute(ActionData $data, Collection $errors = null): ActionData
    {
        $this->parametersResolver->resolve($data, $this, $errors, true);

        if (!$this->isAllowed($data, $errors)) {
            throw new ForbiddenActionGroupException(
                sprintf('ActionGroup "%s" is not allowed', $this->definition->getName())
            );
        }

        $preExecuteEvent = new ActionGroupPreExecuteEvent($data, $this->getDefinition(), $errors);
        $this->eventDispatcher->dispatch($preExecuteEvent);

        $this->executeService($data, $errors);

        $executeEvent = new ActionGroupExecuteEvent($data, $this->getDefinition(), $errors);
        $this->eventDispatcher->dispatch($executeEvent);

        return $data;
    }

    #[\Override]
    public function getDefinition(): ActionGroupDefinition
    {
        return $this->definition;
    }

    #[\Override]
    public function isAllowed(ActionData $data, Collection $errors = null): bool
    {
        $guardEvent = new ActionGroupGuardEvent($data, $this->getDefinition(), $errors);
        $this->eventDispatcher->dispatch($guardEvent);

        if (!$guardEvent->isAllowed()) {
            return false;
        }

        return true;
    }

    #[\Override]
    public function getParameters(): array
    {
        if ($this->parameters === null) {
            $this->fillParameterArgumentMapping();

            $this->parameters = [];
            $reflection = new \ReflectionMethod($this->service, $this->method);
            foreach ($reflection->getParameters() as $methodArg) {
                $parameter = $this->createParameterByMethodArgument($methodArg);

                $this->parameters[$parameter->getName()] = $parameter;
            }
        }

        return $this->parameters;
    }

    private function createParameterByMethodArgument(\ReflectionParameter $methodParameter): Parameter
    {
        $argName = $methodParameter->getName();
        $parameterName = $this->argumentNameToParameterName[$argName] ?? null;
        $parameterConfig = $this->parametersConfig[$parameterName] ?? [];

        $parameter = new Parameter($parameterName ?? $argName);
        $parameter->setAllowsNull($methodParameter->allowsNull());

        if (array_key_exists('default', $parameterConfig)) {
            $parameter->setDefault($parameterConfig['default']);
        }

        if (!empty($parameterConfig['type']) || $methodParameter->hasType()) {
            $parameter->setType($parameterConfig['type'] ?? $methodParameter->getType()?->getName());
        }

        if (!empty($parameterConfig['type'])
            || ($methodParameter->isOptional() && $methodParameter->isDefaultValueAvailable())
        ) {
            $parameter->setDefault($parameterConfig['type'] ?? $methodParameter->getDefaultValue());
        }

        $parameter->setMessage($parameterConfig['message'] ?? null);

        return $parameter;
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

    private function executeService(ActionData $data, ?Collection $errors = null): void
    {
        try {
            // call_user_func_array allows to use named arguments
            $result = call_user_func_array(
                [$this->service, $this->method],
                $this->getMethodArguments($data)
            );

            if ($this->returnValueName) {
                $result = [$this->returnValueName => $result];
            } elseif (!$result instanceof ActionData && !is_array($result)) {
                $result = [self::RESULT_VALUE_KEY => $result];
            }

            if (is_array($result)) {
                $this->mapResultToContext($data, $result);
            }
        } catch (ActionGroup\Exception $e) {
            $this->processException($errors, $e);
        }
    }
}
