<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that a mailbox has at least one folder for sent emails.
 */
class MailboxOriginValidator extends ConstraintValidator
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof MailboxOrigin) {
            throw new UnexpectedTypeException($constraint, MailboxOrigin::class);
        }

        if (!$value instanceof EmailOrigin) {
            return;
        }

        // try to find sent folder at root level
        if ($value->getFolder(FolderType::SENT)) {
            return;
        }

        // try to find sent folder in sub folders
        if ($this->findSentMailFolderInSubFolders($value)) {
            return;
        }

        $this->context->addViolation(
            $constraint->message,
            [
                '%button%' => $this->translator->trans('oro.imap.configuration.connect_and_retrieve_folders'),
            ]
        );
    }

    private function findSentMailFolderInSubFolders(EmailOrigin $value): bool
    {
        $folders = $value->getFolders();
        foreach ($folders as $folder) {
            if ($folder->hasSubFolders() && true === $this->findSentMailFolder($folder->getSubFolders())) {
                return true;
            }
        }

        return false;
    }

    private function findSentMailFolder(iterable $folders): bool
    {
        foreach ($folders as $folder) {
            if ($folder->getType() === FolderType::SENT) {
                return true;
            }
            if ($folder->hasSubFolders() && true === $this->findSentMailFolder($folder->getSubFolders())) {
                return true;
            }
        }

        return false;
    }
}
