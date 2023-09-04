<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\EmailBundle\Api\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Prepares the Email model created by "update" action to save into the database.
 */
class PrepareEmailModelToSave implements ProcessorInterface
{
    private EmailEntityBuilder $emailEntityBuilder;
    private TranslatorInterface $translator;

    public function __construct(EmailEntityBuilder $emailEntityBuilder, TranslatorInterface $translator)
    {
        $this->emailEntityBuilder = $emailEntityBuilder;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var EmailModel $emailModel */
        $emailModel = $context->getData();

        $this->processBody($emailModel, $context);
    }

    private function processBody(EmailModel $emailModel, CustomizeFormDataContext $context): void
    {
        /** @var FormInterface $emailBodyForm */
        $emailBodyForm = $context->findFormField('body');
        if (!FormUtil::isSubmittedAndValid($emailBodyForm)) {
            return;
        }

        /** @var Email $email */
        $email = $emailModel->getEntity();
        $emailBodyModel = $emailModel->getBody();
        $emailBody = $email->getEmailBody();
        if (null === $emailBodyModel) {
            if (null !== $emailBody) {
                FormUtil::addNamedFormError(
                    $emailBodyForm,
                    Constraint::VALUE,
                    $this->translator->trans('oro.email.body_remove_not_allowed.message', [], 'validators')
                );
            }
        } elseif (null !== $emailBody) {
            FormUtil::addNamedFormError(
                $emailBodyForm,
                Constraint::VALUE,
                $this->translator->trans('oro.email.body_change_not_allowed.message', [], 'validators')
            );
        } else {
            $emailBody = $this->emailEntityBuilder->body(
                $emailBodyModel->getContent(),
                $emailBodyModel->getType() === 'html'
            );
            $email->setEmailBody($emailBody);
            $email->setBodySynced(true);
            $context->addAdditionalEntity($emailBody);
        }
    }
}
