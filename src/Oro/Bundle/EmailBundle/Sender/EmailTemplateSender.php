<?php

namespace Oro\Bundle\EmailBundle\Sender;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Factory\EmailModelFromEmailTemplateFactory;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Responsible for sending email templates in preferred recipient's language when recipient entities given or in
 * a specific language to a set of email addresses.
 */
class EmailTemplateSender implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private EmailModelFromEmailTemplateFactory $emailModelFromEmailTemplateFactory;

    private EmailModelSender $emailModelSender;

    public function __construct(
        EmailModelFromEmailTemplateFactory $emailModelFromEmailTemplateFactory,
        EmailModelSender $emailModelSender
    ) {
        $this->emailModelFromEmailTemplateFactory = $emailModelFromEmailTemplateFactory;
        $this->emailModelSender = $emailModelSender;
        $this->logger = new NullLogger();
    }

    /**
     * @param From $from
     * @param EmailHolderInterface|array<EmailHolderInterface> $recipients
     * @param EmailTemplateCriteria|string $templateName
     * @param array $templateParams
     * @param array $templateContext Email template context. Example:
     *  [
     *      'localization' => Localization|int $localization,
     *      // ... other context parameters supported by the existing candidates names
     *      // providers {@see EmailTemplateCandidatesProviderInterface}
     *  ]
     *
     * @return EmailUser|null
     */
    public function sendEmailTemplate(
        From $from,
        EmailHolderInterface|array $recipients,
        EmailTemplateCriteria|string $templateName,
        array $templateParams = [],
        array $templateContext = []
    ): ?EmailUser {
        try {
            $emailModel = $this->emailModelFromEmailTemplateFactory
                ->createEmailModel($from, $recipients, $templateName, $templateParams, $templateContext);

            return $this->emailModelSender->send($emailModel, $emailModel->getOrigin());
        } catch (\Throwable $exception) {
            $recipientsEmails = array_map(
                static fn (EmailHolderInterface $recipient) => $recipient->getEmail(),
                !is_array($recipients) ? [$recipients] : $recipients
            );
            $this->logger->error(
                'Failed to send an email to {recipients_emails} using "{template_name}" email template: {message}',
                [
                    'exception' => $exception,
                    'recipients' => $recipients,
                    'recipients_emails' => $recipientsEmails,
                    'template_name' => $templateName,
                    'template_params' => $templateParams,
                    'message' => $exception->getMessage(),
                ]
            );
        }

        return null;
    }
}
