<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\Common\Collections\Collection;
use Nette\PhpGenerator\TraitType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldAccessorsHelper;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldIterator;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendedEntityFieldsProcessor;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendEntityMetadataProvider;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Extend entities autocomplete generator.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExtendAutocompleteGenerator
{
    public function __construct(
        protected ConfigManager $configManager,
        protected ContainerInterface $locator,
        protected string $storageDir,
        protected LoggerInterface $logger
    ) {
        ExtendedEntityFieldsProcessor::initialize(
            $locator->get(EntityFieldIterator::class),
            $locator->get(ExtendEntityMetadataProvider::class)
        );
    }

    public function generate(): void
    {
        try {
            ExtendClassLoadingUtils::ensureDirExists(ExtendClassLoadingUtils::getEntityCacheDir($this->storageDir));
            $classes = "<?php\n";
            $classes .= $this->buildPhpFileNamespace();
            $extendConfigs = $this->configManager->getProvider('extend')
                ->getConfigs(null, true);
            foreach ($extendConfigs as $config) {
                $schema = $config->get('schema');
                // Exclude enums and custom entities.
                if (
                    null === $schema
                    || str_starts_with($schema['class'], ExtendClassLoadingUtils::getEntityNamespace())
                ) {
                    continue;
                }
                $classes .= $this->buildPhpAutocompleteTrait($schema);
            }
            $this->writePhpFile($this->getAutocompleteClassPath(), $classes);
        } catch (\Throwable $exception) {
            $this->logger->error('Extend autocomplete generation failed.', ['exception' => $exception]);
        }
    }

    protected function getAutocompleteClassPath(): string
    {
        return ExtendClassLoadingUtils::getAutocompleteClassesPath($this->storageDir);
    }

    private function buildPhpFileNamespace(): string
    {
        return sprintf("\nnamespace %s;\n", ExtendClassLoadingUtils::getAutocompleteNamespace());
    }

    protected function buildAutocompletePhpDocs(array $schema): string
    {
        $result = "\n" . '/** ' . "\n";
        $extendMethods = EntityPropertyInfo::getExtendedMethods($schema['entity']);
        $extendProperties = EntityPropertyInfo::getExtendedProperties($schema['entity']);
        $result .= ' * @see \\' . $schema['entity'] . "\n *\n";
        if (empty($extendProperties) && empty($extendMethods)) {
            return '';
        }
        $entityMetadata = ExtendedEntityFieldsProcessor::getEntityMetadata($schema['entity']);
        foreach ($extendProperties as $propertyName) {
            $format = ' * @property %s $%s';
            $type = $this->getTypedPropertyFormat($propertyName, $entityMetadata);

            if (str_starts_with($type, '?')) {
                $type = str_replace('?', '', $type) . '|null';
            }

            $arguments = [$type, $propertyName];
            $result .= sprintf($format, ...$arguments) . "\n";
        }
        foreach ($extendMethods as $methodName) {
            $format = ' * @method ';
            $format .=  $this->getTypedFormat(
                $methodName,
                EntityPropertyInfo::getExtendedMethodInfo($schema['entity'], $methodName),
                $extendProperties,
                $entityMetadata
            );
            $result .= $format . "\n";
        }
        $result .= ' */' . "\n";

        return $result;
    }

    protected function getTypedPropertyFormat(string $propertyName, ConfigInterface $entityMetadata): string
    {
        $entityValues = $entityMetadata->getValues();
        if (!empty($entityValues['relation'])) {
            foreach ($entityValues['relation'] as $relation) {
                if ($relation['field_id']->getFieldName() === $propertyName) {
                    return $relation['field_id']->getFieldType() === 'manyToMany'
                        ? '\\' . Collection::class
                        : '?\\' . $relation['target_entity'];
                }
            }
        }

        return $this->getTypedPropertyFormatFormMeta($propertyName, $entityMetadata);
    }

    protected function getTypedPropertyFormatFormMeta(string $propertyName, ConfigInterface $entityMetadata): string
    {
        $entityValues = $entityMetadata->getValues();

        if (empty($entityValues['schema']['doctrine'][$entityValues['schema']['entity']]['fields'][$propertyName])) {
            return '';
        }
        $propertyConf = $entityValues['schema']['doctrine'][$entityValues['schema']['entity']]['fields'][$propertyName];
        $isNullable = isset($propertyConf['nullable']) && $propertyConf['nullable'] ? '?' : '';

        return isset($propertyConf['type']) ? $isNullable . $this->getMappedType($propertyConf['type']) : '';
    }

    protected function isMethodHasArgument(string $methodName): bool
    {
        return $this->isAdderMethod($methodName)
            || $this->isSetterMethod($methodName)
            || $this->isHasSupportMethod($methodName)
            || $this->isRemoveMethod($methodName);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getTypedFormat(
        string $methodName,
        array $fieldConfig,
        array $extendProperties,
        ConfigInterface $entityMetadata
    ): string {
        if (empty($fieldConfig) || !isset($fieldConfig[ExtendEntityMetadataProvider::FIELD_NAME])) {
            foreach ($extendProperties as $propertyName) {
                if (!$this->isPropertyMatched($methodName, $propertyName)) {
                    continue;
                }
                $propertyType = $this->getTypedPropertyFormatFormMeta($propertyName, $entityMetadata);
                $returnType = $propertyType;

                if (empty($propertyType)) {
                    continue;
                }

                if (str_starts_with($returnType, '?')) {
                    $returnType = str_replace('?', '', $returnType) . '|null';
                }

                return $this->isMethodHasArgument($methodName)
                    ? $methodName . '(' . str_replace('?', '', $propertyType) . ' $value)'
                    : $returnType . ' ' . $methodName . '() ';
            }
            $methodsArguments = $this->isMethodHasArgument($methodName) ? '($value)' : '()';

            return $methodName . $methodsArguments;
        }
        $formatFromRelation = $this->getFormatFromRelation(
            $methodName,
            $fieldConfig[ExtendEntityMetadataProvider::FIELD_NAME],
            $entityMetadata
        );
        if (null !== $formatFromRelation) {
            return $formatFromRelation;
        }
        $formatFromMeta = $this->getFormatFromMeta(
            $methodName,
            $fieldConfig[ExtendEntityMetadataProvider::FIELD_NAME],
            $entityMetadata
        );
        if (null !== $formatFromMeta) {
            return $formatFromMeta;
        }

        return '()';
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getFormatFromRelation(
        string $methodName,
        string $fieldName,
        ConfigInterface $entityMetadata,
    ): ?string {
        if (empty($entityMetadata->getValues()['relation'])) {
            return null;
        }

        $currentEntity = '\\' . $entityMetadata->getValues()['schema']['entity'];
        foreach ($entityMetadata->getValues()['relation'] as $relation) {
            if ($relation['field_id']->getFieldName() !== $fieldName) {
                continue;
            }
            $targetEntity = '\\' . $relation['target_entity'];
            if ($relation['field_id']->getFieldType() === 'manyToMany') {
                $collection = '\\' . Collection::class;
                if ($this->isAdderMethod($methodName)) {
                    return 'void ' . $methodName . '(' . $targetEntity . ' $value)';
                } elseif ($this->isGetterMethod($methodName)) {
                    // Check if the method name is singular
                    return EntityFieldAccessorsHelper::getterName($fieldName) !== $methodName
                        ? $targetEntity . '|null ' . $methodName . '()'
                        : $collection  . ' ' . $methodName . '()';
                } elseif ($this->isRemoveMethod($methodName)) {
                    return 'void ' . $methodName . '(' . $targetEntity . ' $value)';
                } elseif ($this->isDefaultMethod($methodName)) {
                    return $targetEntity . '|null ' . $methodName . '()';
                } elseif ($this->isSetterMethod($methodName)) {
                    // Check if the method name is singular
                    return EntityFieldAccessorsHelper::setterName($fieldName) !== $methodName
                        ? $currentEntity . ' ' . $methodName . '(?' . $targetEntity . ' $value)'
                        : 'void ' . $methodName . '(' . $collection . ' $value)';
                }
                return 'void ' . $methodName . '()';
            }
            $getFormatedByMethodName = $this->getFormatedByMethodType($methodName, $targetEntity);
            if (null !== $getFormatedByMethodName) {
                return $getFormatedByMethodName;
            }

            return $this->isSetterMethod($methodName)
                ? $currentEntity . ' ' . $methodName . '(?' . $targetEntity . ' $value)'
                : $targetEntity . '|null ' . $methodName . '()';
        }

        return null;
    }

    protected function getFormatedByMethodType(string $methodName, string $targetEntity): ?string
    {
        if ($this->isAdderMethod($methodName)) {
            return 'void ' . $methodName . '(' . $targetEntity . ' $value)';
        } elseif ($this->isGetterMethod($methodName)) {
            return $targetEntity . '|null ' . $methodName . '()';
        } elseif ($this->isRemoveMethod($methodName)) {
            return 'void ' . $methodName . '(' . $targetEntity . ' $value)';
        } elseif ($this->isDefaultMethod($methodName)) {
            return $targetEntity . '|null ' . $methodName . '()';
        }

        return null;
    }

    protected function getFormatFromMeta(
        string $methodName,
        string $fieldName,
        ConfigInterface $entityMetadata,
    ): ?string {
        $schema = $entityMetadata->getValues()['schema'];
        $issetDoctrineConfig = isset($schema['doctrine'][$schema['entity']]['fields'][$fieldName]);
        if ($this->isMethodHasArgument($methodName)) {
            $returnType = $this->isSetterMethod($methodName) ? '\\' . $schema['entity'] : 'bool';
            if ($this->isRemoveMethod($methodName) || $this->isAdderMethod($methodName)) {
                $returnType = 'void';
            }
            $formatValue = $issetDoctrineConfig
                ? '(' . $this->getArgType($schema['doctrine'][$schema['entity']]['fields'][$fieldName]) . ' $value)'
                : '($value)';

            return $returnType . ' ' . $methodName . $formatValue;
        }
        $isGetter = str_starts_with($methodName, 'get');
        if ($isGetter && $issetDoctrineConfig) {
            return $this->getReturnType($schema['doctrine'][$schema['entity']]['fields'][$fieldName])
                . ' ' . $methodName . '() ';
        }

        return null;
    }

    protected function getArgType(array $doctrineField): string
    {
        if (!isset($doctrineField['type'])) {
            return '';
        }

        return isset($doctrineField['nullable']) && $doctrineField['nullable']
            ? '?' . $this->getMappedType($doctrineField['type'])
            : $this->getMappedType($doctrineField['type']);
    }

    protected function getReturnType(array $doctrineField): string
    {
        if (!isset($doctrineField['type'])) {
            return '';
        }

        return isset($doctrineField['nullable']) && $doctrineField['nullable']
            ? $this->getMappedType($doctrineField['type']) . '|null'
            : $this->getMappedType($doctrineField['type']);
    }

    protected function getMappedType(string $type): string
    {
        $mapTypes = [
            'json' => 'array',
            'simple_array' => 'array',
            'text' => 'string',
            'money' => 'float',
            'date' => '\DateTime',
            'datetime' => '\DateTime',
            'wysiwyg' => 'string',
            'wysiwyg_style' => 'string',
            'wysiwyg_properties' => 'array'
        ];

        return $mapTypes[$type] ?? $type;
    }

    protected function buildPhpAutocompleteTrait(array $schema): string
    {
        $baseName = ExtendClassLoadingUtils::getAutocompleteClassName($schema['entity']);
        $class = new TraitType($baseName);
        $classHeader = $this->buildAutocompletePhpDocs($schema);
        if (empty($classHeader)) {
            return '';
        }

        return $classHeader . $class;
    }

    protected function isPropertyMatched(string $methodName, string $propertyName): bool
    {
        return match ($methodName) {
            EntityFieldAccessorsHelper::getterName($propertyName) => true,
            EntityFieldAccessorsHelper::setterName($propertyName) => true,
            EntityFieldAccessorsHelper::adderName($propertyName) => true,
            EntityFieldAccessorsHelper::removerName($propertyName) => true,
            default => false,
        };
    }

    protected function isAdderMethod(string $methodName): bool
    {
        return str_starts_with($methodName, 'add');
    }

    protected function isGetterMethod(string $methodName): bool
    {
        return str_starts_with($methodName, 'get');
    }

    protected function isSetterMethod(string $methodName): bool
    {
        return str_starts_with($methodName, 'set');
    }

    protected function isHasSupportMethod(string $methodName): bool
    {
        return str_starts_with($methodName, 'support') || str_starts_with($methodName, 'has');
    }

    protected function isRemoveMethod(string $methodName): bool
    {
        return str_starts_with($methodName, 'remove');
    }

    protected function isDefaultMethod(string $methodName): bool
    {
        return str_starts_with($methodName, 'default');
    }

    private function writePhpFile(string $path, string $content): void
    {
        $oldContentCrc = sprintf("%u", crc32((string)@file_get_contents($path)));
        $contentCrc = sprintf("%u", crc32($content));
        if ($oldContentCrc === $contentCrc) {
            return;
        }
        file_put_contents($path, $content);
        clearstatcache(true, $path);
    }
}
