<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EmailBundle\Api\Model\Email as EmailModel;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that an body for Email entity is set when attachments are not empty.
 */
class ValidateEmailBodyExists implements ProcessorInterface
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            return;
        }

        /** @var EmailModel $emailModel */
        $emailModel = $context->getData();
        if (null === $emailModel->getBody() && !$emailModel->getEmailAttachments()->isEmpty()) {
            FormUtil::addNamedFormError(
                $context->findFormField('body'),
                'email attachment without body constraint',
                $this->translator->trans('oro.email.attachments_without_body.message', [], 'validators')
            );
        }
    }
}
