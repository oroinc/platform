<?php

namespace Oro\Bundle\EmailBundle\Validator;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that email box have at least one sent folder.
 */
class MailboxOriginValidator extends ConstraintValidator
{
    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
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

    /**
     * @deprecated. The search for sent folder should be done not only in sub folders of inbox folder.
     */
    protected function inboxHasSubFolderWithType($value, $folderType)
    {
        /** @var EmailFolder $folder */
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
