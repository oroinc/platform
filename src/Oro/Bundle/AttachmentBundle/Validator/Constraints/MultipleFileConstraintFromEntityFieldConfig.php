<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for checking max number of the uploaded file according to entity field config.
 */
class MultipleFileConstraintFromEntityFieldConfig extends Constraint
{
    /** @var string */
    protected $entityClass;

    /** @var string */
    protected $fieldName;

    /** @var string */
    public $message = 'oro.attachment.max_number_of_files.files';

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        if (!isset($options['entityClass'])) {
            throw new \InvalidArgumentException('Option entityClass is required');
        }

        if (!isset($options['fieldName'])) {
            throw new \InvalidArgumentException('Option fieldName is required');
        }

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }
}
