<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for the following fields for Email entity:
 * * "from", "toRecipients", "ccRecipients", "bccRecipients"
 * * "importance"
 * * "type" for the email body
 */
class ComputeEmailFields implements ProcessorInterface
{
    private EmailAddressHelper $emailAddressHelper;

    public function __construct(EmailAddressHelper $emailAddressHelper)
    {
        $this->emailAddressHelper = $emailAddressHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        $this->computeFromAddress($context, $data);
        $this->computeRecipientAddresses($context, $data, 'toRecipients', EmailRecipient::TO);
        $this->computeRecipientAddresses($context, $data, 'ccRecipients', EmailRecipient::CC);
        $this->computeRecipientAddresses($context, $data, 'bccRecipients', EmailRecipient::BCC);
        $this->computeImportance($context, $data);
        $this->computeBodyType($context, $data);
        $context->setData($data);
    }

    private function computeFromAddress(CustomizeLoadedDataContext $context, array &$data): void
    {
        $fieldName = 'from';
        if (!$context->isFieldRequested($fieldName)) {
            return;
        }

        $data[$fieldName] = $this->buildEmailAddress(
            $this->emailAddressHelper->extractEmailAddressName($data['fromName']),
            $data['fromEmailAddress']['email'] ?? null
        );
    }

    private function computeRecipientAddresses(
        CustomizeLoadedDataContext $context,
        array &$data,
        string $fieldName,
        string $recipientType
    ): void {
        if (!$context->isFieldRequested($fieldName)) {
            return;
        }

        $recipients = [];
        foreach ($data['recipients'] as $item) {
            if ($item['type'] !== $recipientType) {
                continue;
            }
            $recipient = $this->buildEmailAddress(
                $this->emailAddressHelper->extractEmailAddressName($item['name']),
                $item['emailAddress']['email'] ?? null
            );
            if (null !== $recipient) {
                $recipients[] = $recipient;
            }
        }
        $data[$fieldName] = $recipients;
    }

    private function buildEmailAddress(?string $name, ?string $email): ?array
    {
        if (null === $name && null === $email) {
            return null;
        }

        return ['name' => $name, 'email' => $email];
    }

    private function computeImportance(CustomizeLoadedDataContext $context, array &$data): void
    {
        $importanceFieldName = $context->getResultFieldName('importance');
        if (!$context->isFieldRequested($importanceFieldName)) {
            return;
        }

        $data[$importanceFieldName] = $this->transformImportanceValue($data[$importanceFieldName]);
    }

    private function transformImportanceValue(?int $value): string
    {
        switch ($value) {
            case Email::HIGH_IMPORTANCE:
                return 'high';
            case Email::LOW_IMPORTANCE:
                return 'low';
            default:
                return 'normal';
        }
    }

    private function computeBodyType(CustomizeLoadedDataContext $context, array &$data): void
    {
        $bodyFieldName = $context->getResultFieldName('emailBody');
        if (!$context->isFieldRequested($bodyFieldName)) {
            return;
        }

        if (isset($data[$bodyFieldName])) {
            $typeFieldName = $context->getConfig()->getField($bodyFieldName)->getTargetEntity()
                ->findFieldNameByPropertyPath('bodyIsText');
            $data[$bodyFieldName][$typeFieldName] = $data[$bodyFieldName][$typeFieldName] ? 'text' : 'html';
        } else {
            $data[$bodyFieldName] = null;
        }
    }
}
