<?php

namespace Oro\Bundle\ImapBundle\Validator;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EmailFoldersValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof UserEmailOrigin) {
            $value = $value->getFolders();
        }

        if (!$value instanceof Collection) {
            return;
        }

        if ($this->hasSelectedFolders($value)) {
            return;
        }

        $this->context->addViolation($constraint->message);
    }

    /**
     * @param Collection $value
     *
     * @return bool
     */
    protected function hasSelectedFolders(Collection $value)
    {
        foreach ($value as $emailFolder) {
            if (!$emailFolder instanceof EmailFolder) {
                continue;
            }

            if ($emailFolder->isSyncEnabled()) {
                return true;
            }

            if (!$emailFolder->getSubFolders() instanceof Collection || $emailFolder->getSubFolders()->isEmpty()) {
                continue;
            }

            if ($this->hasSelectedFolders($emailFolder->getSubFolders())) {
                return true;
            }
        }

        return false;
    }
}
