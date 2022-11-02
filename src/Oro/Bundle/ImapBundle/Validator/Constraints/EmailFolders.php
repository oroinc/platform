<?php

namespace Oro\Bundle\ImapBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that a mailbox contains at least one folder.
 */
class EmailFolders extends Constraint
{
    public string $message = 'oro.imap.validator.configuration.folders_are_not_selected';
}
