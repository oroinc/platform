<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

/**
 * The container for data that are used as a context during rendering a TWIG templates in the sandbox.
 */
class TemplateData
{
    private const PATH_SEPARATOR          = '.';
    private const COMPUTED_PATH_SEPARATOR = '__';

    /** @var array */
    private $data;

    /** @var EntityVariableComputer */
    private $entityVariableComputer;

    /** @var EntityDataAccessor */
    private $entityDataAccessor;

    /** @var string */
    private $systemSectionName;

    /** @var string */
    private $entitySectionName;

    /** @var string */
    private $computedSectionName;

    public function __construct(
        array $data,
        EntityVariableComputer $entityVariableComputer,
        EntityDataAccessor $entityDataAccessor,
        string $systemSectionName,
        string $entitySectionName,
        string $computedSectionName
    ) {
        $this->data = $data;
        $this->entityVariableComputer = $entityVariableComputer;
        $this->entityDataAccessor = $entityDataAccessor;
        $this->systemSectionName = $systemSectionName;
        $this->entitySectionName = $entitySectionName;
        $this->computedSectionName = $computedSectionName;
    }

    /**
     * Gets array representation of data this object contains.
     *
     * @return array [section name => data, ...]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Checks if this object contains values for system variables.
     */
    public function hasSystemVariables(): bool
    {
        return isset($this->data[$this->systemSectionName]);
    }

    /**
     * Gets values of system variables.
     *
     * @throws \LogicException if this object does not contain values of system variables
     */
    public function getSystemVariables(): array
    {
        if (!isset($this->data[$this->systemSectionName])) {
            throw new \LogicException('This object does not contain values of system variables.');
        }

        return $this->data[$this->systemSectionName];
    }

    /**
     * Checks if this object contains the root entity.
     */
    public function hasRootEntity(): bool
    {
        return isset($this->data[$this->entitySectionName]);
    }

    /**
     * Gets the root entity.
     *
     * @return object
     *
     * @throws \LogicException if this object does not contain the root entity
     */
    public function getRootEntity()
    {
        if (!isset($this->data[$this->entitySectionName])) {
            throw new \LogicException('This object does not contain the root entity.');
        }

        return $this->data[$this->entitySectionName];
    }

    /**
     * Gets the value of the given entity related variable.
     *
     * @param string $variable The path to the variable
     *
     * @return mixed
     *
     * @throws \LogicException if the given variable is not an entity related one
     */
    public function getEntityVariable(string $variable)
    {
        if (!str_starts_with($variable, $this->entitySectionName)) {
            if (!str_contains($variable, self::PATH_SEPARATOR)) {
                throw new \LogicException(sprintf(
                    'Expected "%s" variable, got "%s".',
                    $this->entitySectionName,
                    $variable
                ));
            }
            throw new \LogicException(sprintf(
                'The variable "%s" must start with "%s".',
                $variable,
                $this->entitySectionName . self::PATH_SEPARATOR
            ));
        }

        return $this->doGetEntityVariable($variable);
    }

    /**
     * Gets the path to the parent variable.
     *
     * @param string $variable The path to the variable
     *
     * @return string
     *
     * @throws \LogicException if the given variable is not valid
     */
    public function getParentVariablePath(string $variable): string
    {
        $lastSeparatorPos = \strrpos($variable, self::PATH_SEPARATOR);
        if (false === $lastSeparatorPos) {
            throw new \LogicException(sprintf(
                'The variable "%s" must have at least 2 elements delimited by "%s".',
                $variable,
                self::PATH_SEPARATOR
            ));
        }

        return \substr($variable, 0, $lastSeparatorPos);
    }

    /**
     * Checks if this object contains a computed value for the given variable.
     *
     * @param string $variable The path to the variable
     *
     * @return bool
     */
    public function hasComputedVariable(string $variable): bool
    {
        if (!isset($this->data[$this->computedSectionName])) {
            return false;
        }

        return \array_key_exists(
            $this->buildComputedVariableName($variable),
            $this->data[$this->computedSectionName]
        );
    }

    /**
     * Gets a computed value for the given variable.
     *
     * @param string $variable The path to the variable
     *
     * @return mixed
     *
     * @throws \LogicException if this object does not contain a computed value of the given variable
     */
    public function getComputedVariable(string $variable)
    {
        if (!isset($this->data[$this->computedSectionName])) {
            throw $this->createNoComputedVariableException($variable);
        }
        $computedVariableName = $this->buildComputedVariableName($variable);
        if (!\array_key_exists($computedVariableName, $this->data[$this->computedSectionName])) {
            throw $this->createNoComputedVariableException($variable);
        }

        return $this->data[$this->computedSectionName][$computedVariableName];
    }

    /**
     * Sets the computed value for the given variable.
     *
     * @param string $variable The path to the variable
     * @param mixed  $value    The variable value
     */
    public function setComputedVariable(string $variable, $value): void
    {
        $this->data[$this->computedSectionName][$this->buildComputedVariableName($variable)] = $value;
    }

    /**
     * Gets the path to a computed variable that replaces the given variable.
     *
     * @param string $variable The path to the variable
     *
     * @return string The path to the computed variable
     */
    public function getComputedVariablePath(string $variable): string
    {
        return $this->computedSectionName . self::PATH_SEPARATOR . $this->buildComputedVariableName($variable);
    }

    /**
     * Gets the original path to the variable that was replaced by the given computed variable.
     *
     * @param string $computedVariablePath The path to the computed variable
     *
     * @return string The path to the variable
     *
     * @throws \LogicException if the given path is not a path to a computed variable
     */
    public function getVariablePath(string $computedVariablePath): string
    {
        $prefix = $this->computedSectionName . self::PATH_SEPARATOR;
        if (!str_starts_with($computedVariablePath, $prefix)) {
            throw new \LogicException(sprintf(
                'The computed variable "%s" must start with "%s".',
                $computedVariablePath,
                $prefix
            ));
        }

        return \str_replace(
            self::COMPUTED_PATH_SEPARATOR,
            self::PATH_SEPARATOR,
            \substr($computedVariablePath, \strlen($prefix))
        );
    }

    /**
     * @param string $variable
     *
     * @return mixed
     */
    public function doGetEntityVariable(string $variable)
    {
        if ($variable === $this->entitySectionName) {
            return $this->getRootEntity();
        }

        if ($this->hasComputedVariable($variable)) {
            return $this->getComputedVariable($variable);
        }

        $computedPath = $this->entityVariableComputer->computeEntityVariable($variable, $this);
        if ($computedPath) {
            return $this->getComputedVariable($variable);
        }

        $lastSeparatorPos = \strrpos($variable, self::PATH_SEPARATOR);
        $parentValue = $this->doGetEntityVariable(\substr($variable, 0, $lastSeparatorPos));
        if (!\is_object($parentValue)) {
            return null;
        }

        $value = null;
        $propertyName = \substr($variable, $lastSeparatorPos + 1);
        if (!$this->entityDataAccessor->tryGetValue($parentValue, $propertyName, $value)) {
            return null;
        }

        return $value;
    }

    private function buildComputedVariableName(string $variable): string
    {
        return \str_replace(self::PATH_SEPARATOR, self::COMPUTED_PATH_SEPARATOR, $variable);
    }

    private function createNoComputedVariableException(string $variable): \LogicException
    {
        return new \LogicException(sprintf('The computed variable "%s" does not exist.', $variable));
    }
}
