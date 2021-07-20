<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\FileValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Decorates FileValidator with the following:
 * - fetches mime types and max file size from system config if they are not specified explicitly in validation.yml
 */
class FileConstraintFromSystemConfigValidator extends ConstraintValidator
{
    /** @var FileValidator */
    private $fileValidator;

    /** @var FileConstraintsProvider */
    private $fileConstraintsProvider;

    public function __construct(
        FileValidator $fileValidator,
        FileConstraintsProvider $mimeTypesProvider
    ) {
        $this->fileValidator = $fileValidator;
        $this->fileConstraintsProvider = $mimeTypesProvider;
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
     * @param FileConstraintFromSystemConfig $constraint
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (empty($constraint->mimeTypes)) {
            $constraint->mimeTypes = $this->fileConstraintsProvider->getMimeTypes();
        }

        if (empty($constraint->maxSize) && $constraint->maxSizeConfigPath) {
            $constraint->maxSize = $this->fileConstraintsProvider
                ->getMaxSizeByConfigPath($constraint->maxSizeConfigPath);
        }

        $this->fileValidator->validate($value, $constraint);
    }
}
