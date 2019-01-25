<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * This constraint can be used to check allowed MIME types.
 */
class MimeType extends Constraint
{
    private const TYPES = ['any', 'file', 'image'];

    public $message = 'oro.attachment.mimetypes.not_allowed';

    /** @var string */
    public $type = 'any';

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
        if (!\in_array($this->type, self::TYPES, true)) {
            throw new ConstraintDefinitionException(
                sprintf('The option "type" must be one of "%s"', implode('", "', self::TYPES))
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'type';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
