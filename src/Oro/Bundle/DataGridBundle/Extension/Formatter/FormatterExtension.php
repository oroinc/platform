<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a way to format a datagrid column value depends on its data-type.
 */
class FormatterExtension extends AbstractExtension
{
    /** @var string[] */
    private array $propertyTypes;
    private ContainerInterface $propertyContainer;
    private TranslatorInterface $translator;

    /**
     * @param string[]            $propertyTypes
     * @param ContainerInterface  $propertyContainer
     * @param TranslatorInterface $translator
     */
    public function __construct(
        array $propertyTypes,
        ContainerInterface $propertyContainer,
        TranslatorInterface $translator
    ) {
        $this->propertyTypes = $propertyTypes;
        $this->propertyContainer = $propertyContainer;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config): bool
    {
        if (!parent::isApplicable($config)) {
            return false;
        }

        $columns = $config->offsetGetOr(Configuration::COLUMNS_KEY, []);
        $properties = $config->offsetGetOr(Configuration::PROPERTIES_KEY, []);
        $applicable = $columns || $properties;
        $this->processConfigs($config);

        return $applicable;
    }

    /**
     * Validate configs nad fill default values
     */
    public function processConfigs(DatagridConfiguration $config): void
    {
        $columns = $config->offsetGetOr(Configuration::COLUMNS_KEY, []);
        $properties = $config->offsetGetOr(Configuration::PROPERTIES_KEY, []);

        // validate extension configuration and normalize by setting default values
        $columnsNormalized = $this->validateConfigurationByType($columns, Configuration::COLUMNS_KEY);
        $propertiesNormalized = $this->validateConfigurationByType($properties, Configuration::PROPERTIES_KEY);

        // replace config values by normalized, extra keys passed directly
        $config->offsetSet(Configuration::COLUMNS_KEY, array_replace_recursive($columns, $columnsNormalized));
        $config->offsetSet(Configuration::PROPERTIES_KEY, array_replace_recursive($properties, $propertiesNormalized));
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result): void
    {
        $rows = $result->getData();
        $columns = $config->offsetGetOr(Configuration::COLUMNS_KEY, []);
        $properties = $config->offsetGetOr(Configuration::PROPERTIES_KEY, []);
        $toProcess = array_merge($columns, $properties);

        foreach ($rows as $key => $row) {
            $currentRow = [];
            foreach ($toProcess as $name => $propertyConfig) {
                $property = $this->getPropertyObject(PropertyConfiguration::createNamed($name, $propertyConfig));
                $currentRow[$name] = $property->getValue($row);
            }
            $rows[$key] = $currentRow;
        }

        $result->setData($rows);
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data): void
    {
        // get only columns here because columns will be represented on frontend
        $columns = $config->offsetGetOr(Configuration::COLUMNS_KEY, []);

        $propertiesMetadata = [];
        foreach ($columns as $name => $fieldConfig) {
            $fieldConfig = PropertyConfiguration::createNamed($name, $fieldConfig);
            $metadata = $this->getPropertyObject($fieldConfig)->getMetadata();

            // translate label on backend
            if ($metadata[PropertyInterface::TRANSLATABLE_KEY] && $metadata['label']) {
                $metadata['label'] = $this->translator->trans($metadata['label']);
            }
            $propertiesMetadata[] = $metadata;
        }

        $data->offsetAddToArray('columns', $propertiesMetadata);
    }

    private function getPropertyObject(PropertyConfiguration $config): PropertyInterface
    {
        $type = (string) $config->offsetGet(Configuration::TYPE_KEY);
        if (!$this->propertyContainer->has($type)) {
            throw new RuntimeException(sprintf('The "%s" formatter not found.', $type));
        }

        /** @var PropertyInterface $property */
        $property = $this->propertyContainer->get($type);
        $property->init($config);

        return $property;
    }

    private function validateConfigurationByType(array $config, string $type): array
    {
        return $this->validateConfiguration(
            new Configuration($this->propertyTypes, $type),
            [$type => $config]
        );
    }
}
