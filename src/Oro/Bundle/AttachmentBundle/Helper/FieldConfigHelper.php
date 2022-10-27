<?php

namespace Oro\Bundle\AttachmentBundle\Helper;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

/**
 * Provide helpful methods to work with image/file field configs
 */
class FieldConfigHelper
{
    const FILE_TYPE = 'file';
    const IMAGE_TYPE = 'image';
    const MULTI_FILE_TYPE = 'multiFile';
    const MULTI_IMAGE_TYPE = 'multiImage';

    public static function isImageField(ConfigIdInterface $fieldConfigId): bool
    {
        return in_array($fieldConfigId->getFieldType(), [self::IMAGE_TYPE, self::MULTI_IMAGE_TYPE], true);
    }

    public static function isFileField(ConfigIdInterface $fieldConfigId): bool
    {
        return in_array($fieldConfigId->getFieldType(), [self::FILE_TYPE, self::MULTI_FILE_TYPE], true);
    }

    public static function isMultiField(ConfigIdInterface $fieldConfigId): bool
    {
        return in_array($fieldConfigId->getFieldType(), [self::MULTI_FILE_TYPE, self::MULTI_IMAGE_TYPE], true);
    }
}
