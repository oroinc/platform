<?php

namespace Oro\Bundle\AttachmentBundle\Validator;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\ConfigBundle\Config\ConfigManager as Configuration;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The validator that can be used to check that a file is allowed to be uploaded.
 */
class ConfigFileValidator
{
    /** @var ValidatorInterface */
    private $validator;

    /** @var ConfigManager */
    private $configManager;

    /** @var Configuration */
    private $config;

    /**
     * @param ValidatorInterface $validator
     * @param ConfigManager      $configManager
     * @param Configuration      $config
     */
    public function __construct(ValidatorInterface $validator, ConfigManager $configManager, Configuration $config)
    {
        $this->validator = $validator;
        $this->configManager = $configManager;
        $this->config = $config;
    }

    /**
     * @param ComponentFile $file      A file object to be validated
     * @param string        $dataClass The FQCN of a parent entity
     * @param string        $fieldName The name of file/image field
     *
     * @return ConstraintViolationListInterface
     */
    public function validate($file, $dataClass, $fieldName = '')
    {
        if ($fieldName === '') {
            $config = $this->configManager->getEntityConfig('attachment', $dataClass);
            $mimeTypes = MimeTypesConverter::convertToArray($config->get('mimetypes'));
            if (!$mimeTypes) {
                $mimeTypes = array_unique(array_merge(
                    MimeTypesConverter::convertToArray($this->config->get('oro_attachment.upload_file_mime_types')),
                    MimeTypesConverter::convertToArray($this->config->get('oro_attachment.upload_image_mime_types'))
                ));
            }
        } else {
            $config = $this->configManager->getFieldConfig('attachment', $dataClass, $fieldName);
            $mimeTypes = MimeTypesConverter::convertToArray($config->get('mimetypes'));
            if (!$mimeTypes) {
                $configKey = sprintf('oro_attachment.upload_%s_mime_types', $config->getId()->getFieldType());
                $mimeTypes = MimeTypesConverter::convertToArray($this->config->get($configKey));
            }
        }

        $maxFileSize = $config->get('maxsize');
        if (null !== $maxFileSize) {
            $maxFileSize *= 1024 * 1024;
        }

        return $this->validator->validate(
            $file,
            [new FileConstraint(['maxSize' => $maxFileSize, 'mimeTypes' => $mimeTypes])]
        );
    }
}
