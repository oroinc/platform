<?php

namespace Oro\Bundle\EmailBundle\Sender;

use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Provider\ParentMessageIdProvider;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\MessageIdHelper;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Creates {@see SymfonyEmail} model from the specified {@see EmailModel}.
 */
class EmailFactory
{
    private ParentMessageIdProvider $parentMessageIdProvider;

    private EmailAddressHelper $emailAddressHelper;

    public function __construct(
        ParentMessageIdProvider $parentMessageIdProvider,
        EmailAddressHelper $emailAddressHelper
    ) {
        $this->parentMessageIdProvider = $parentMessageIdProvider;
        $this->emailAddressHelper = $emailAddressHelper;
    }

    public function createFromEmailModel(EmailModel $emailModel): SymfonyEmail
    {
        $this->assertHasRequiredData($emailModel);

        $symfonyEmail = new SymfonyEmail();

        $parentMessageId = $this->parentMessageIdProvider->getParentMessageIdToReply($emailModel);
        if ($parentMessageId) {
            $parentMessageId = MessageIdHelper::unwrapMessageId($parentMessageId);
            $symfonyEmail->getHeaders()
                ->addTextHeader('References', $parentMessageId)
                ->addTextHeader('In-Reply-To', $parentMessageId);
        }

        $from = $this->getAddresses($emailModel->getFrom());

        $symfonyEmail->date(new \DateTime('now', new \DateTimeZone('UTC')));
        $symfonyEmail->from(...$from);
        $symfonyEmail->replyTo(...$from);
        $symfonyEmail->returnPath($from[0]->getAddress());
        $symfonyEmail->to(...$this->getAddresses($emailModel->getTo()));
        $symfonyEmail->cc(...$this->getAddresses($emailModel->getCc()));
        $symfonyEmail->bcc(...$this->getAddresses($emailModel->getBcc()));

        $symfonyEmail->subject($emailModel->getSubject());

        $body = (string)$emailModel->getBody();
        if ($emailModel->getType() === 'html') {
            $symfonyEmail->html($body);
        } else {
            $symfonyEmail->text($body);
        }

        $this->addAttachments($symfonyEmail, $emailModel->getAttachments() ?: []);

        return $symfonyEmail;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function assertHasRequiredData(EmailModel $model): void
    {
        if (!$model->getFrom()) {
            throw new \InvalidArgumentException('Sender can not be empty');
        }

        if (!$model->getTo() && !$model->getCc() && !$model->getBcc()) {
            throw new \InvalidArgumentException('Recipient can not be empty');
        }
    }

    /**
     * Converts emails addresses to a form acceptable by {@see SymfonyEmail}
     *
     * @param iterable|string|null $addresses Examples of correct email addresses: john@example.com, <john@example.com>,
     *                                        John Smith <john@example.com> or "John Smith" <john@example.com>
     *
     * @return SymfonyAddress[]
     *
     * @throws \InvalidArgumentException
     */
    private function getAddresses(iterable|string|null $addresses): array
    {
        $result = [];
        foreach ((array)$addresses as $address) {
            $result[] = new SymfonyAddress(
                $this->emailAddressHelper->extractPureEmailAddress($address),
                $this->emailAddressHelper->extractEmailAddressName($address) ?: ''
            );
        }

        return $result;
    }

    /**
     * @param SymfonyEmail $message
     * @param iterable<EmailAttachmentModel> $attachments
     */
    private function addAttachments(SymfonyEmail $message, iterable $attachments): void
    {
        foreach ($attachments as $emailAttachmentModel) {
            $emailAttachmentEntity = $emailAttachmentModel->getEmailAttachment();
            if (!$emailAttachmentEntity) {
                continue;
            }

            $emailAttachmentContent = $emailAttachmentEntity->getContent();
            $decodedContent = ContentDecoder::decode(
                $emailAttachmentContent->getContent(),
                $emailAttachmentContent->getContentTransferEncoding()
            );

            if ($emailAttachmentEntity->getEmbeddedContentId() !== null) {
                $message->embed(
                    $decodedContent,
                    $emailAttachmentEntity->getEmbeddedContentId(),
                    $emailAttachmentEntity->getContentType()
                );
            } else {
                $message->attach(
                    $decodedContent,
                    $emailAttachmentEntity->getFileName(),
                    $emailAttachmentEntity->getContentType()
                );
            }
        }
    }
}
