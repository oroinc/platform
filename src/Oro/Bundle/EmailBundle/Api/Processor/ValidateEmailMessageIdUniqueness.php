<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EmailBundle\Api\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that Message-ID is unique for a new email.
 */
class ValidateEmailMessageIdUniqueness implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private TranslatorInterface $translator;

    public function __construct(DoctrineHelper $doctrineHelper, TranslatorInterface $translator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            return;
        }

        /** @var EmailModel $emailModel */
        $emailModel = $context->getData();
        if ($this->isEmailExist($emailModel->getMessageId())) {
            FormUtil::addNamedFormError(
                $context->getForm(),
                Constraint::CONFLICT,
                $this->translator->trans('oro.email.message_id_conflict.message', [], 'validators'),
                null,
                Response::HTTP_CONFLICT
            );
        }
    }

    private function isEmailExist(string $messageId): bool
    {
        $rows = $this->doctrineHelper->createQueryBuilder(Email::class, 'e')
            ->select('e.id')
            ->where('e.messageId = :messageId')
            ->setParameter('messageId', $messageId)
            ->getQuery()
            ->getArrayResult();

        return \count($rows) > 0;
    }
}
