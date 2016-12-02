<?php

namespace Oro\Bundle\NoteBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ContextNotEmpty extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.note.context.not_empty.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_note.context.not_empty_validator';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
