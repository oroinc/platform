<?php

namespace Oro\Bundle\DigitalAssetBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager as SystemConfigManager;
use Oro\Bundle\DigitalAssetBundle\Provider\MimeTypesProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\FileValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Decorates FileValidator with the following:
 * - fetches mime types and max file size from system config if they are not specified explicitly in validation.yml
 */
class DigitalAssetSourceFileValidator extends ConstraintValidator
{
    /** @var FileValidator */
    private $fileValidator;

    /** @var SystemConfigManager */
    private $systemConfigManager;

    /** @var MimeTypesProvider */
    private $mimeTypesProvider;

    /**
     * @param FileValidator $fileValidator
     * @param SystemConfigManager $systemConfigManager
     * @param MimeTypesProvider $mimeTypesProvider
     */
    public function __construct(
        FileValidator $fileValidator,
        SystemConfigManager $systemConfigManager,
        MimeTypesProvider $mimeTypesProvider
    ) {
        $this->fileValidator = $fileValidator;
        $this->systemConfigManager = $systemConfigManager;
        $this->mimeTypesProvider = $mimeTypesProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExecutionContextInterface $context): void
    {
        parent::initialize($context);

        $this->fileValidator->initialize($context);
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (empty($constraint->mimeTypes)) {
            $constraint->mimeTypes = $this->mimeTypesProvider->getMimeTypes();
        }

        if (empty($constraint->maxSize)) {
            $constraint->maxSize =
                $this->systemConfigManager->get('oro_attachment.maxsize') * Configuration::BYTES_MULTIPLIER;
        }

        $this->fileValidator->validate($value, $constraint);
    }
}
