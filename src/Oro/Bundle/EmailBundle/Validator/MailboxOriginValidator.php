<?php

namespace Oro\Bundle\EmailBundle\Validator;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MailboxOriginValidator extends ConstraintValidator
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
        if (!$value instanceof EmailOrigin) {
            return;
        }

        if ($value->getFolder(FolderType::SENT)) {
            return;
        }

        $this->context->addViolation(
            $constraint->message,
            [
                '%button%' => $this->translator->trans('oro.imap.configuration.connect_and_retrieve_folders'),
            ]
        );
    }
}
