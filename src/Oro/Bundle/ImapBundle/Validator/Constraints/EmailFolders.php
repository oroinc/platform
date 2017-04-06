<?php

namespace Oro\Bundle\ImapBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class EmailFolders extends Constraint
{
    /** @var string */
    public $message = 'oro.imap.validator.configuration.folders_are_not_selected';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_imap.validator.email_folders';
    }
}
