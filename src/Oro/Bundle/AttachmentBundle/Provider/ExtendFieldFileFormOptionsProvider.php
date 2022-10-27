<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsProviderInterface;

/**
 * Manages "isExternalFile" form option based on entity field config.
 */
class ExtendFieldFileFormOptionsProvider implements ExtendFieldFormOptionsProviderInterface
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

        $options = ['isExternalFile' => (bool)$attachmentFieldConfig->get('is_stored_externally', false, false)];

        if (in_array($formFieldConfigId->getFieldType(), ['multiFile', 'multiImage'])) {
            $options = [
                'entry_options' => [
                    // {@see \Oro\Bundle\AttachmentBundle\Form\Type\FileItemType::buildForm()}
                    'file_options' => $options,
                ],
            ];
        }

        return $options;
    }
}
