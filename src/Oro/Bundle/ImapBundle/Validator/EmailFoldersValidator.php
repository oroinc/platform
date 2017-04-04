<?php

namespace Oro\Bundle\ImapBundle\Validator;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;

class EmailFoldersValidator extends ConstraintValidator
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Collection) {
            return;
        }

        if ($this->hasSelectedFolders($value)) {
            return;
        }

        $this->context->addViolation($constraint->message);
    }

    /**
     * @param PersistentCollection $value
     *
     * @return bool
     */
    protected function hasSelectedFolders($value)
    {
        /** @var EmailFolder $emailFolder */
        foreach ($value as $emailFolder) {
            if ($emailFolder->isSyncEnabled()) {
                return true;
            }
        }

        return false;
    }
}
