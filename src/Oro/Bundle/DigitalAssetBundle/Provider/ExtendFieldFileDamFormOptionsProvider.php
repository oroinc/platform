<?php

namespace Oro\Bundle\DigitalAssetBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsProviderInterface;

/**
 * Manages "dam_widget_enabled" form option based on entity field config.
 */
class ExtendFieldFileDamFormOptionsProvider implements ExtendFieldFormOptionsProviderInterface
{
    private EntityConfigManager $entityConfigManager;

    public function __construct(EntityConfigManager $entityConfigManager)
    {
        $this->entityConfigManager = $entityConfigManager;
    }

    public function getOptions(string $className, string $fieldName): array
    {
        $className = ClassUtils::getRealClass($className);
        $formFieldConfig = $this->entityConfigManager->getFieldConfig('form', $className, $fieldName);

        /** @var FieldConfigId $formFieldConfigId */
        $formFieldConfigId = $formFieldConfig->getId();
        if (!in_array($formFieldConfigId->getFieldType(), ['file', 'image', 'multiFile', 'multiImage'])) {
            return [];
        }

        $attachmentFieldConfig = $this->entityConfigManager->getFieldConfig('attachment', $className, $fieldName);

        $options = [
            'dam_widget_enabled' => $attachmentFieldConfig->get('use_dam', false, false) &&
                !$attachmentFieldConfig->get('is_stored_externally', false, false),
        ];

        if (in_array($formFieldConfigId->getFieldType(), ['multiFile', 'multiImage'])) {
            $options = ['entry_options' => ['file_options' => $options]];
        }

        return $options;
    }
}
