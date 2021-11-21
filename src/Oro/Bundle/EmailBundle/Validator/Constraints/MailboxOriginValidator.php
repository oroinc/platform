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
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof MailboxOrigin) {
            throw new UnexpectedTypeException($constraint, MailboxOrigin::class);
        }

        if (!$value instanceof EmailOrigin) {
            return;
        }

        if ($value->getFolder(FolderType::SENT)) {
            return;
        }

        if ($this->inboxHasSubFolderWithType($value, FolderType::SENT)) {
            return;
        }

        $this->context->addViolation(
            $constraint->message,
            [
                '%button%' => $this->translator->trans('oro.imap.configuration.connect_and_retrieve_folders'),
            ]
        );
    }

    private function inboxHasSubFolderWithType(EmailOrigin $value, string $folderType): bool
    {
        $folder = $value->getFolder(FolderType::INBOX);
        if ($folder) {
            $subFolders = $folder->getSubFolders();
            if ($subFolders->count() > 0) {
                foreach ($subFolders as $subFolder) {
                    if ($subFolder->getType() === $folderType) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
