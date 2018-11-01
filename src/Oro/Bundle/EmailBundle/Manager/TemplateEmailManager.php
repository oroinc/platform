<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;

/**
 * Responsible for sending email templates in preferred recipient's language when recipient entities given or in
 * a specific language to a set of email addresses.
 */
class TemplateEmailManager
{
    public const CONTENT_TYPE_HTML = 'text/html';
    public const CONTENT_TYPE_TEXT = 'text/plain';

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var EmailRenderer
     */
    private $emailRenderer;

    /**
     * @var PreferredLanguageProviderInterface
     */
    private $languageProvider;

    /**
     * @var Processor
     */
    private $mailerProcessor;

    /**
     * @param ManagerRegistry $registry
     * @param \Swift_Mailer $mailer
     * @param EmailRenderer $emailRenderer
     * @param PreferredLanguageProviderInterface $languageProvider
     * @param Processor $mailerProcessor
     */
    public function __construct(
        ManagerRegistry $registry,
        \Swift_Mailer $mailer,
        EmailRenderer $emailRenderer,
        PreferredLanguageProviderInterface $languageProvider,
        Processor $mailerProcessor
    ) {
        $this->registry = $registry;
        $this->mailer = $mailer;
        $this->emailRenderer = $emailRenderer;
        $this->languageProvider = $languageProvider;
        $this->mailerProcessor = $mailerProcessor;
    }

    /**
     * @param From $sender
     * @param iterable|EmailHolderInterface[] $recipients
     * @param EmailTemplateCriteria $criteria
     * @param array $templateParams
     * @param null|array $failedRecipients array of failed recipients
     * @return int sent messages, note that a separate messages is sent to each recipient
     * @throws InvalidArgumentException
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

        $emailTemplate = $this->loadTemplate($criteria, $language);

        list($subject, $body) = $this->emailRenderer->compileMessage($emailTemplate, $templateParams);

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setBody($body)
            ->setContentType($this->getTemplateContentType($emailTemplate));

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
     * @param EmailTemplateCriteria $criteria
     * @param string $language
     * @return EmailTemplate
     * @throws \LogicException
     */
    private function loadTemplate(EmailTemplateCriteria $criteria, string $language): EmailTemplate
    {
        /** @var EmailTemplateRepository $repository */
        $repository = $this->registry->getManagerForClass(EmailTemplate::class)->getRepository(EmailTemplate::class);
        $template = $repository->findOneLocalized($criteria, $language);

        if (!$template) {
            throw new \LogicException(
                sprintf('Email template with conditions "%s" not found', \json_encode([
                    'name' => $criteria->getName(),
                    'entityName' => $criteria->getEntityName(),
                ]))
            );
        }

        return $template;
    }

    /**
     * @param EmailTemplate $emailTemplate
     * @return string
     */
    private function getTemplateContentType(EmailTemplate $emailTemplate): string
    {
        return $emailTemplate->getType() === EmailTemplate::TYPE_HTML
            ? self::CONTENT_TYPE_HTML
            : self::CONTENT_TYPE_TEXT;
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
