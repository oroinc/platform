<?php

namespace Oro\Bundle\CurrencyBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Event\LoadFieldsMetadata;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;

/**
 * As currency functionality is represented by three fields (from entity side) we have to hide such fields from
 *  permissions configuration page and add only one that will affect all of them.
 * Adds virtual field into permissions list, the name of such field will be taken from `target` property.
 * Walks through fields with defined `target` in `multicurrency` scope and makes changes in FieldSecurityMetadata
 *  sets `alias` to `target` and `isHidden` to TRUE.
 * The field with defined `virtual_field` in `multicurrency` scope is used to retrieve the label to be used for virtual
 *  field mentioned above.
 */
class AclLoadFieldMetadataListener
{
    /** @var ConfigProvider */
    protected $multicurrencyConfigProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /**
     * @param ConfigManager       $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->multicurrencyConfigProvider = $configManager->getProvider('multicurrency');
        $this->entityConfigProvider = $configManager->getProvider('entity');
    }

    /**
     * @param LoadFieldsMetadata $event
     */
    public function onLoad(LoadFieldsMetadata $event)
    {
        $className = $event->getClassName();
        $fields = $event->getFields();
        $fieldsToAdd = [];

        /** @var Config[] $multicurrencyFields */
        $multicurrencyFields = $this->multicurrencyConfigProvider->filter(
            function (ConfigInterface $config) {
                return $config->has('target');
            },
            $className
        );

        foreach ($multicurrencyFields as $field) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $field->getId();
            $fieldName = $fieldConfigId->getFieldName();
            $target = $field->get('target');

            if (isset($fields[$fieldName])) {
                /** @var FieldSecurityMetadata $existingField */
                $existingField = $fields[$fieldName];
                $fieldsToAdd[$fieldName] = new FieldSecurityMetadata(
                    $existingField->getFieldName(),
                    $existingField->getLabel(),
                    $existingField->getPermissions(),
                    $existingField->getDescription(),
                    $target,
                    true
                );
                unset($fields[$fieldName]);
            }

            if ($field->get('virtual_field')
                && $target
                && !isset($fieldsToAdd[$target])
            ) {
                $fieldsToAdd[$target] = new FieldSecurityMetadata(
                    $target,
                    $this->entityConfigProvider->getConfig($className, $fieldName)->get('label'),
                    ['VIEW', 'CREATE', 'EDIT']
                );
            }
        }

        if (count($fieldsToAdd)) {
            $event->setFields(array_merge($fields, $fieldsToAdd));
        }
    }
}
