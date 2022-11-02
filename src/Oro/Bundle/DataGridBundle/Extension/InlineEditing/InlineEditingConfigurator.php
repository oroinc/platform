<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldBlackList;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Configure inline editing for a given data grid configuration.
 * Configure inline editing for a column is inline editing is applicable or for all supporting columns.
 */
class InlineEditingConfigurator
{
    private InlineEditColumnOptionsGuesser $guesser;
    private EntityClassNameHelper $entityClassNameHelper;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        InlineEditColumnOptionsGuesser $inlineEditColumnOptionsGuesser,
        EntityClassNameHelper $entityClassNameHelper,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->guesser = $inlineEditColumnOptionsGuesser;
        $this->entityClassNameHelper = $entityClassNameHelper;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function isInlineEditingSupported(DatagridConfiguration $config): bool
    {
        return !empty($this->getEntityName($config));
    }

    public function configureInlineEditingForGrid(DatagridConfiguration $config): void
    {
        $entityName = $this->getEntityName($config);
        if (!$entityName) {
            return;
        }
        $configItems = $config->offsetGetOr(Configuration::BASE_CONFIG_KEY, []);
        $configItems[Configuration::CONFIG_ENTITY_KEY] = $entityName;

        $configuration = new Configuration(Configuration::BASE_CONFIG_KEY);
        $normalizedConfigItems = $this->validateConfiguration(
            $configuration,
            [Configuration::BASE_CONFIG_KEY => $configItems]
        );

        //according to ACL disable inline editing for the whole grid
        if (!$this->isGranted($configItems)) {
            $normalizedConfigItems[Configuration::CONFIG_ENABLE_KEY] = false;
        }

        // replace config values by normalized, extra keys passed directly
        $resultConfigItems = array_replace_recursive($configItems, $normalizedConfigItems);
        if (is_null($resultConfigItems['save_api_accessor']['default_route_parameters']['className'])) {
            $resultConfigItems['save_api_accessor']['default_route_parameters']['className'] =
                $this->entityClassNameHelper->getUrlSafeClassName($entityName);
        }
        $config->offsetSet(Configuration::BASE_CONFIG_KEY, $resultConfigItems);
    }

    public function configureInlineEditingForColumn(DatagridConfiguration $config, string $columnName): void
    {
        $entityName = $this->getEntityName($config);
        $objectIdentity = new ObjectIdentity('entity', $entityName);
        $columnPath = sprintf('[%s][%s]', FormatterConfiguration::COLUMNS_KEY, $columnName);
        $behaviour = $config->offsetGetByPath(Configuration::BEHAVIOUR_CONFIG_PATH);
        $column = $config->offsetGetByPath($columnPath);

        $newColumn = $this->guesser->getColumnOptions(
            $columnName,
            $entityName,
            $column,
            $behaviour
        );

        // Check access to edit field in Class level.
        // If access not granted - skip inline editing for such field.
        if (!$this->isFieldEditable($newColumn, $column, $objectIdentity, $columnName)) {
            if (array_key_exists(Configuration::BASE_CONFIG_KEY, $column)) {
                $column[Configuration::BASE_CONFIG_KEY][Configuration::CONFIG_ENABLE_KEY] = false;
            }
            $config->offsetSetByPath($columnPath, $column);

            return;
        }

        // frontend type, type, data_name keys must not be replaced with default value
        $keys = [
            PropertyInterface::FRONTEND_TYPE_KEY,
            PropertyInterface::TYPE_KEY,
            PropertyInterface::DATA_NAME_KEY,
        ];
        foreach ($keys as $key) {
            if (!empty($newColumn[$key])) {
                $column[$key] = $newColumn[$key];
            }
        }

        $column = array_replace_recursive($newColumn, $column);
        $config->offsetSetByPath($columnPath, $column);
    }

    /**
     * Add inline editing where it is possible, do not use ACL, because additional parameters for columns needed.
     */
    public function configureInlineEditingForSupportingColumns(DatagridConfiguration $config): void
    {
        $blackList = EntityFieldBlackList::getValues();
        $columns = $config->offsetGetOr(FormatterConfiguration::COLUMNS_KEY, []);

        foreach ($columns as $columnName => $column) {
            if (in_array($columnName, $blackList, true)) {
                continue;
            }

            $this->configureInlineEditingForColumn($config, $columnName);
        }
    }

    protected function isFieldEditable(
        array $newColumn,
        array $column,
        ObjectIdentity $objectIdentity,
        string $columnName
    ): bool {
        return ($this->isEnabledEdit($newColumn) || $this->isEnabledEdit($column))
            && $this->authorizationChecker->isGranted(
                'EDIT',
                new FieldVote($objectIdentity, $this->getColumnFieldName($columnName, $column))
            );
    }

    /**
     * Returns column data field name
     */
    private function getColumnFieldName(string $columnName, array $column): string
    {
        return $column[Configuration::BASE_CONFIG_KEY]
            [Configuration::EDITOR_KEY][Configuration::VIEW_OPTIONS_KEY][Configuration::VALUE_FIELD_NAME_KEY]
            ?? $columnName;
    }

    private function isEnabledEdit(array $column): bool
    {
        return !empty($column[Configuration::BASE_CONFIG_KEY][Configuration::CONFIG_ENABLE_KEY]);
    }

    private function isGranted(array $configItems): bool
    {
        $acl = !empty($configItems[Configuration::CONFIG_ACL_KEY]) ?
            $configItems[Configuration::CONFIG_ACL_KEY] :
            'EDIT;entity:' . $configItems[Configuration::CONFIG_ENTITY_KEY];

        return $this->authorizationChecker->isGranted($acl);
    }

    private function getEntityName(DatagridConfiguration $config): ?string
    {
        $configItems = $config->offsetGetOr(Configuration::BASE_CONFIG_KEY, []);

        if (!empty($configItems[Configuration::CONFIG_ENTITY_KEY])) {
            return $configItems[Configuration::CONFIG_ENTITY_KEY];
        }

        return $config->getExtendedEntityClassName();
    }

    private function validateConfiguration(ConfigurationInterface $configuration, array $config): array
    {
        $processor = new Processor();

        return $processor->processConfiguration(
            $configuration,
            $config
        );
    }
}
