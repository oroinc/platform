<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\Extra\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\FieldDescriptionUtil;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Version;

trait ResourceConsistencyTestTrait
{
    /**
     * @param string   $entityClass
     * @param string[] $excludedActions
     */
    private function checkResourceConsistency(string $entityClass, array $excludedActions): void
    {
        $actions = $this->getActions(
            [ApiAction::GET, ApiAction::GET_LIST, ApiAction::CREATE, ApiAction::UPDATE],
            $excludedActions
        );
        if (count($actions) < 2) {
            return;
        }

        $entityFields = $this->getFields($entityClass, $actions);

        $baseFields = reset($entityFields);
        $baseAction = key($entityFields);
        array_shift($entityFields);
        $errorMessage = '';
        foreach ($entityFields as $action => $actionFields) {
            $actionErrorMessage = $this->compareFields($baseFields, $baseAction, $actionFields, $action);
            if ($actionErrorMessage) {
                if ($errorMessage) {
                    $errorMessage .= "\n";
                }
                $errorMessage .= $actionErrorMessage;
            }
        }
        if ($errorMessage) {
            $errorMessage .=
                "\n\n" . 'To make a field read-only, set "mapped" form option to false, e.g:'
                . "\n" . 'api:'
                . "\n" . '    entities:'
                . "\n" . '        Acme\Bundle\AcmeBundle\Entity\SomeEntity:'
                . "\n" . '            fields:'
                . "\n" . '                myField:'
                . "\n" . '                    form_options:'
                . "\n" . '                        mapped: false'
                . "\n\n" . sprintf(
                    'Do not forget to add the "%s" hint to the description of such field.',
                    FieldDescriptionUtil::MODIFY_READ_ONLY_FIELD_DESCRIPTION
                );
            self::fail($errorMessage);
        }
    }

    /**
     * @param string[] $allActions
     * @param string[] $excludedActions
     *
     * @return string[]
     */
    private function getActions(array $allActions, array $excludedActions): array
    {
        $actions = [];
        foreach ($allActions as $action) {
            if (!in_array($action, $excludedActions, true)) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    private function getMetadata(string $entityClass, string $action): EntityMetadata
    {
        /** @var ConfigProvider $configProvider */
        $configProvider = self::getContainer()->get('oro_api.config_provider');
        /** @var MetadataProvider $metadataProvider */
        $metadataProvider = self::getContainer()->get('oro_api.metadata_provider');

        $version = Version::LATEST;
        $requestType = $this->getRequestType();
        $config = $configProvider->getConfig(
            $entityClass,
            $version,
            $requestType,
            [new EntityDefinitionConfigExtra($action)]
        );

        return $metadataProvider->getMetadata(
            $entityClass,
            $version,
            $requestType,
            $config->getDefinition(),
            [new ActionMetadataExtra($action)]
        );
    }

    /**
     * @param string   $entityClass
     * @param string[] $actions
     *
     * @return array [action => [field name, ...], ...]
     */
    private function getFields(string $entityClass, array $actions): array
    {
        $entityFields = [];
        foreach ($actions as $action) {
            $metadata = $this->getMetadata($entityClass, $action);
            $fields = array_merge(
                array_keys($metadata->getFields()),
                array_keys($metadata->getAssociations())
            );
            sort($fields);
            $entityFields[$action] = array_values($fields);
        }

        return $entityFields;
    }

    /**
     * @param string[] $baseFields
     * @param string   $baseAction
     * @param string[] $actionFields
     * @param string   $action
     *
     * @return string
     */
    private function compareFields(array $baseFields, string $baseAction, array $actionFields, string $action): string
    {
        $newFields = [];
        $missingFields = [];
        foreach ($actionFields as $field) {
            if (ApiAction::GET !== $baseAction && !in_array($field, $baseFields, true)) {
                $newFields[] = $field;
            }
        }
        foreach ($baseFields as $field) {
            if (!in_array($field, $actionFields, true)) {
                $missingFields[] = $field;
            }
        }

        $errorMessage = '';
        if (!empty($newFields)) {
            $errorMessage .= sprintf(
                "\n" . '  Fields that exist in the action "%s", but does not exist in the action "%s": %s',
                $action,
                $baseAction,
                implode(', ', $newFields)
            );
        }
        if (!empty($missingFields)) {
            $errorMessage .= sprintf(
                "\n" . '  Fields that exist in the action "%s", but does not exist in the action "%s": %s',
                $baseAction,
                $action,
                implode(', ', $missingFields)
            );
        }
        if ($errorMessage) {
            $errorMessage = sprintf(
                'The action "%s" should have the same fields as the action "%s".%s',
                $action,
                $baseAction,
                $errorMessage
            );
        }

        return $errorMessage;
    }
}
