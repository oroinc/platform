<?php

namespace Oro\Bundle\AttachmentBundle\Validator;

use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;

use Oro\Bundle\ConfigBundle\Config\ConfigManager as Configuration;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class ConfigFileValidator
{
    /** @var Validator */
    protected $validator;

    /** @var Configuration */
    protected $config;

    /** @var ConfigProvider */
    protected $attachmentConfigProvider;

    /**
     * @param Validator         $validator
     * @param ConfigManager     $configManager
     * @param Configuration     $config
     */
    public function __construct(Validator $validator, ConfigManager $configManager, Configuration $config)
    {
        $this->validator                = $validator;
        $this->attachmentConfigProvider = $configManager->getProvider('attachment');
        $this->config                   = $config;
    }

    /**
     * @param string          $dataClass Parent entity class name
     * @param File|Attachment $entity    File entity
     * @param string          $fieldName Field name where new file/image field was added
     *
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public function validate($dataClass, $entity, $fieldName = '')
    {
        /** @var Config $entityAttachmentConfig */
        if ($fieldName === '') {
            $entityAttachmentConfig = $this->attachmentConfigProvider->getConfig($dataClass);
            $mimeTypes              = $this->getMimeArray($entityAttachmentConfig->get('mimetypes'));
            if (!$mimeTypes) {
                $mimeTypes = array_merge(
                    $this->getMimeArray($this->config->get('oro_attachment.upload_file_mime_types')),
                    $this->getMimeArray($this->config->get('oro_attachment.upload_image_mime_types'))
                );
            }
        } else {
            $entityAttachmentConfig = $this->attachmentConfigProvider->getConfig($dataClass, $fieldName);
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $entityAttachmentConfig->getId();
            if ($fieldConfigId->getFieldType() === 'file') {
                $configValue = 'upload_file_mime_types';
            } else {
                $configValue = 'upload_image_mime_types';
            }
            $mimeTypes = $this->getMimeArray($this->config->get('oro_attachment.' . $configValue));
        }

        $fileSize = $entityAttachmentConfig->get('maxsize') * 1024 * 1024;

        foreach ($mimeTypes as $id => $value) {
            $mimeTypes[$id] = trim($value);
        }

        return $this->validator->validateValue(
            $entity->getFile(),
            [
                new FileConstraint(
                    [
                        'maxSize'   => $fileSize,
                        'mimeTypes' => $mimeTypes
                    ]
                )
            ]
        );
    }

    /**
     * @param string $mimeString
     * @return array
     */
    protected function getMimeArray($mimeString)
    {
        $mimeTypes = explode("\n", $mimeString);
        if (count($mimeTypes) === 1 && $mimeTypes[0] === '') {
            return '';
        }

        return $mimeTypes;
    }
}
