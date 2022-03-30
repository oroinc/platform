<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\FileValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Decorates FileValidator with the following:
 * - fetches mime types and max file size from entity field config
 */
class FileConstraintFromEntityFieldConfigValidator extends ConstraintValidator
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
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof FileConstraintFromEntityFieldConfig) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected instance of %s, got %s',
                    FileConstraintFromEntityFieldConfig::class,
                    get_class($constraint)
                )
            );
        }

        $fileConstraint = new File(
            [
                'mimeTypes' => $this->fileConstraintsProvider->getAllowedMimeTypesForEntityField(
                    $constraint->getEntityClass(),
                    $constraint->getFieldName()
                ),
                'maxSize' => $this->fileConstraintsProvider->getMaxSizeForEntityField(
                    $constraint->getEntityClass(),
                    $constraint->getFieldName()
                ),
                'mimeTypesMessage' => 'oro.attachment.mimetypes.invalid_mime_type',
            ]
        );

        $this->fileValidator->validate($value, $fileConstraint);
    }
}
