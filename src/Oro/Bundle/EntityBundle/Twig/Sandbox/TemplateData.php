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

    /** @var string */
    private $systemSectionName;

    /** @var string */
    private $entitySectionName;

    /** @var string */
    private $computedSectionName;

    /**
     * @param array  $data
     * @param string $systemSectionName
     * @param string $entitySectionName
     * @param string $computedSectionName
     */
    public function __construct(
        array $data,
        string $systemSectionName,
        string $entitySectionName,
        string $computedSectionName
    ) {
        $this->data = $data;
        $this->systemSectionName = $systemSectionName;
        $this->entitySectionName = $entitySectionName;
        $this->computedSectionName = $computedSectionName;
    }

    /**
     * @return array [section name => data, ...]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function hasSystemData(): bool
    {
        return \array_key_exists($this->systemSectionName, $this->data);
    }

    /**
     * @return array
     */
    public function getSystemData(): array
    {
        return $this->data[$this->systemSectionName];
    }

    /**
     * @return bool
     */
    public function hasEntityData(): bool
    {
        return \array_key_exists($this->entitySectionName, $this->data);
    }

    /**
     * @return object
     */
    public function getEntityData()
    {
        return $this->data[$this->entitySectionName];
    }

    /**
     * @param string $variable
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
     * @param string $variable
     *
     * @return mixed
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
     * @param string $variable
     * @param mixed  $value
     */
    public function setComputedVariable(string $variable, $value): void
    {
        $this->data[$this->computedSectionName][$this->buildComputedVariableName($variable)] = $value;
    }

    /**
     * @param string $variable
     *
     * @return string
     */
    public function getComputedVariablePath(string $variable): string
    {
        return $this->computedSectionName . self::PATH_SEPARATOR . $this->buildComputedVariableName($variable);
    }

    /**
     * @param string $computedVariablePath
     *
     * @return string
     */
    public function getVariablePath(string $computedVariablePath): string
    {
        $prefix = $this->computedSectionName . self::PATH_SEPARATOR;
        if (\strpos($computedVariablePath, $prefix) !== 0) {
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
     * @return string
     */
    private function buildComputedVariableName(string $variable): string
    {
        return \str_replace(self::PATH_SEPARATOR, self::COMPUTED_PATH_SEPARATOR, $variable);
    }

    /**
     * @param string $variable
     *
     * @return \LogicException
     */
    private function createNoComputedVariableException(string $variable): \LogicException
    {
        return new \LogicException(sprintf('The computed variable "%s" does not exist.', $variable));
    }
}
