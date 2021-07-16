<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface as ConfigProvider;

/**
 * This class implements the logic to computation values of entity related variables.
 */
class EntityVariableComputer
{
    private const PATH_SEPARATOR = '.';
    private const PROCESSOR      = 'processor';

    /** @var ConfigProvider */
    private $configProvider;

    /** @var VariableProcessorRegistry */
    private $variableProcessors;

    /** @var EntityDataAccessor */
    private $entityDataAccessor;

    public function __construct(
        ConfigProvider $configProvider,
        VariableProcessorRegistry $variableProcessors,
        EntityDataAccessor $entityDataAccessor
    ) {
        $this->configProvider = $configProvider;
        $this->variableProcessors = $variableProcessors;
        $this->entityDataAccessor = $entityDataAccessor;
    }

    /**
     * Computes the value of the given variable and adds it to the given data object.
     *
     * @param string       $variable
     * @param TemplateData $data
     *
     * @return string|null The path of the computed variable if its value was computed; otherwise, NULL
     */
    public function computeEntityVariable(string $variable, TemplateData $data): ?string
    {
        if ($data->hasComputedVariable($variable)) {
            return $data->getComputedVariablePath($variable);
        }

        $path = \explode(self::PATH_SEPARATOR, $variable);
        if (\count($path) === 2) {
            $result = null;
            if ($this->tryProcessEntityVariable($variable, $data->getRootEntity(), $path[1], $data)
                && $data->hasComputedVariable($variable)
            ) {
                $result = $data->getComputedVariablePath($variable);
            }

            return $result;
        }

        return $this->tryComputeNestedEntityVariable(
            $variable,
            $data,
            $path[0],
            $data->getRootEntity(),
            \array_slice($path, 1)
        );
    }

    /**
     * @param string       $variable
     * @param TemplateData $data
     * @param string       $rootVariable
     * @param object       $rootEntity
     * @param string[]     $path
     *
     * @return string|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function tryComputeNestedEntityVariable(
        string $variable,
        TemplateData $data,
        $rootVariable,
        $rootEntity,
        array $path
    ): ?string {
        $pathCount = \count($path);
        if (1 === $pathCount) {
            return $this->computeEntityVariableByPropertyName(
                $variable,
                $rootVariable,
                $rootEntity,
                $path[0],
                $data
            );
        }

        $propertyPath = \implode(self::PATH_SEPARATOR, $path);
        $propertyVariable = \substr($variable, 0, -\strlen($propertyPath))
            . \str_replace(self::PATH_SEPARATOR, '_', $propertyPath);
        if ($this->tryProcessEntityVariable($propertyVariable, $rootEntity, $propertyPath, $data)
            && $data->hasComputedVariable($propertyVariable)
        ) {
            return $data->getComputedVariablePath($propertyVariable);
        }

        $parentValue = null;
        $parentVariable = $rootVariable . self::PATH_SEPARATOR . $path[0];
        if ($data->hasComputedVariable($parentVariable)) {
            $parentValue = $data->getComputedVariable($parentVariable);
        } elseif (!$this->tryProcessEntityVariable($parentVariable, $rootEntity, $path[0], $data)) {
            $parentValue = $this->getEntityFieldValue($rootEntity, $path[0]);
        } elseif ($data->hasComputedVariable($parentVariable)) {
            $parentValue = $data->getComputedVariable($parentVariable);
        }

        if (\is_array($parentValue) && $data->hasComputedVariable($parentVariable)) {
            return $data->getComputedVariablePath($parentVariable)
                . '.' . \implode(self::PATH_SEPARATOR, \array_slice($path, 1));
        }

        if (!\is_object($parentValue)) {
            return null;
        }

        return $this->tryComputeNestedEntityVariable(
            $variable,
            $data,
            $parentVariable,
            $parentValue,
            \array_slice($path, 1)
        );
    }

    private function computeEntityVariableByPropertyName(
        string $variable,
        string $rootVariable,
        $rootEntity,
        string $propertyName,
        TemplateData $data
    ) {
        $result = null;
        if ($this->tryProcessEntityVariable($variable, $rootEntity, $propertyName, $data)) {
            if ($data->hasComputedVariable($variable)) {
                $result = $data->getComputedVariablePath($variable);
            }
        } elseif ($data->hasComputedVariable($rootVariable)) {
            $result = $data->getComputedVariablePath($rootVariable) . self::PATH_SEPARATOR . $propertyName;
        }

        return $result;
    }

    /**
     * @param string       $variable
     * @param object       $entity
     * @param string       $propertyName
     * @param TemplateData $data
     *
     * @return bool
     */
    private function tryProcessEntityVariable(
        string $variable,
        $entity,
        string $propertyName,
        TemplateData $data
    ): bool {
        $processorDefinitions = $this->configProvider->getEntityVariableProcessors(ClassUtils::getClass($entity));
        if (!isset($processorDefinitions[$propertyName][self::PROCESSOR])) {
            return false;
        }

        $processorDefinition = $processorDefinitions[$propertyName];
        if (!$this->variableProcessors->has($processorDefinition[self::PROCESSOR])) {
            return false;
        }

        if (!$data->hasComputedVariable($variable)) {
            $this->variableProcessors->get($processorDefinition[self::PROCESSOR])
                ->process($variable, $processorDefinition, $data);
        }

        return true;
    }

    /**
     * @param object $entity
     * @param string $propertyName
     *
     * @return mixed
     */
    private function getEntityFieldValue($entity, string $propertyName)
    {
        $result = null;
        if (!$this->entityDataAccessor->tryGetValue($entity, $propertyName, $result)) {
            return null;
        }

        return $result;
    }
}
