<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Oro\Bundle\EmailBundle\Exception\EmailTemplateException;
use Oro\Bundle\EmailBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateContentProvider;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;

/**
 * Responsible for sending email templates in preferred recipient's language when recipient entities given or in
 * a specific language to a set of email addresses.
 */
class TemplateEmailManager
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var EmailTemplateContentProvider
     */
    private $emailTemplateContentProvider;

    /**
     * @var PreferredLanguageProviderInterface
     */
    private $languageProvider;

    /**
     * @var Processor
     */
    private $mailerProcessor;

    /**
     * @param \Swift_Mailer $mailer
     * @param PreferredLanguageProviderInterface $languageProvider
     * @param Processor $mailerProcessor
     * @param EmailTemplateContentProvider $emailTemplateContentProvider
     */
    public function __construct(
        \Swift_Mailer $mailer,
        PreferredLanguageProviderInterface $languageProvider,
        Processor $mailerProcessor,
        EmailTemplateContentProvider $emailTemplateContentProvider
    ) {
        $this->mailer = $mailer;
        $this->languageProvider = $languageProvider;
        $this->mailerProcessor = $mailerProcessor;
        $this->emailTemplateContentProvider = $emailTemplateContentProvider;
    }

    /**
     * @param From $sender
     * @param iterable|EmailHolderInterface[] $recipients
     * @param EmailTemplateCriteria $criteria
     * @param array $templateParams
     * @param null|array $failedRecipients array of failed recipients
     * @return int sent messages, note that a separate messages is sent to each recipient
     * @throws InvalidArgumentException
     * @throws EmailTemplateException
     */
    public function sendTemplateEmail(
        From $sender,
        iterable $recipients,
        EmailTemplateCriteria $criteria,
        array $templateParams = [],
        &$failedRecipients = null
    ): int {
        $this->assertRecipients($recipients);

        $sent = 0;
        foreach ($this->groupRecipientsByLanguage($recipients) as $language => $group) {
            $emails = array_map(function (EmailHolderInterface $recipient) {
                return $recipient->getEmail();
            }, $group);

            $sent += $this->sendLocalizedTemplateEmail(
                $language,
                $sender,
                $emails,
                $criteria,
                $templateParams,
                $failedRecipients
            );
        }

        return $sent;
    }

    /**
     * @param string $language
     * @param From $from
     * @param iterable|string[] $toEmails
     * @param EmailTemplateCriteria $criteria
     * @param array $templateParams
     * @param null|array $failedRecipients array of failed recipients
     * @return int sent messages, note that a separate messages is sent to each recipient
     * @throws InvalidArgumentException
     * @throws EmailTemplateException
     */
    private function sendLocalizedTemplateEmail(
        string $language,
        From $from,
        iterable $toEmails,
        EmailTemplateCriteria $criteria,
        array $templateParams = [],
        &$failedRecipients = null
    ): int {
        $this->assertEmails($toEmails);

        $emailTemplateModel =
            $this->emailTemplateContentProvider->getTemplateContent($criteria, $language, $templateParams);

        $message = \Swift_Message::newInstance()
            ->setSubject($emailTemplateModel->getSubject())
            ->setBody($emailTemplateModel->getContent())
            ->setContentType($emailTemplateModel->getType());

        $from->populate($message);

        $this->mailerProcessor->processEmbeddedImages($message);

        $sent = 0;
        foreach ($toEmails as $toEmail) {
            $messageToSend = clone $message;
            $messageToSend->setTo($toEmail);

            $sent += $this->mailer->send($messageToSend, $failedRecipients);
        }

        return $sent;
    }

    /**
     * @param array|EmailHolderInterface[] $recipients
     * @return array
     */
    private function groupRecipientsByLanguage(array $recipients): array
    {
        $groupedRecipients = [];
        foreach ($recipients as $recipient) {
            $groupedRecipients[$this->languageProvider->getPreferredLanguage($recipient)][] = $recipient;
        }

        return $groupedRecipients;
    }

    /**
     * @param array $emails
     * @throws InvalidArgumentException
     */
    private function assertEmails(array $emails): void
    {
        foreach ($emails as $email) {
            if (!\is_string($email)) {
                throw new InvalidArgumentException(
                    sprintf('toEmails should be array of strings, "%s" type in array given.', gettype($email))
                );
            }
        }
    }

    /**
     * @param array $recipients
     * @throws InvalidArgumentException
     */
    private function assertRecipients(array $recipients): void
    {
        foreach ($recipients as $recipient) {
            if (!$recipient instanceof EmailHolderInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'recipients should be array of EmailHolderInterface values, "%s" type in array given.',
                        \is_object($recipient) ? \get_class($recipient) : gettype($recipient)
                    )
                );
            }
        }
    }
}
