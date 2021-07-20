<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ImageValidator as BaseImageValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * The validator checks MIME-type of the uploaded images for compliance with the system settings
 */
class ImageValidator extends BaseImageValidator
{
    /** @var ConfigManager */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Image) {
            throw new UnexpectedTypeException($constraint, Image::class);
        }

        $constraint->mimeTypes = MimeTypesConverter::convertToArray(
            $this->configManager->get('oro_attachment.upload_image_mime_types')
        );

        parent::validate($value, $constraint);
    }
}
