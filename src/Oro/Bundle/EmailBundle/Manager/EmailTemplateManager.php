<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;

/**
 * Responsible for sending email templates in preferred recipient's language when recipient entities given or in
 * a specific language to a set of email addresses.
 */
class EmailTemplateManager
{
    /** @var \Swift_Mailer */
    private $mailer;

    /** @var Processor */
    private $mailerProcessor;

    /** @var LocalizedTemplateProvider */
    private $localizedTemplateProvider;

    public function __construct(
        \Swift_Mailer $mailer,
        Processor $mailerProcessor,
        LocalizedTemplateProvider $localizedTemplateProvider
    ) {
        $this->mailer = $mailer;
        $this->mailerProcessor = $mailerProcessor;
        $this->localizedTemplateProvider = $localizedTemplateProvider;
    }

    /**
     * @param From $sender
     * @param iterable|EmailHolderInterface[] $recipients
     * @param EmailTemplateCriteria $criteria
     * @param array $templateParams
     * @param null|array $failedRecipients
     * @return int
     */
    public function sendTemplateEmail(
        From $sender,
        iterable $recipients,
        EmailTemplateCriteria $criteria,
        array $templateParams = [],
        &$failedRecipients = null
    ): int {
        $sent = 0;
        $templateCollection = $this->localizedTemplateProvider->getAggregated($recipients, $criteria, $templateParams);

        foreach ($templateCollection as $localizedTemplateDTO) {
            $emailTemplate = $localizedTemplateDTO->getEmailTemplate();

            $message = new \Swift_Message();
            $message->setSubject($emailTemplate->getSubject());
            $message->setBody($emailTemplate->getContent());
            $message->setContentType($emailTemplate->getType());

            $sender->populate($message);

            $this->mailerProcessor->processEmbeddedImages($message);

            foreach ($localizedTemplateDTO->getRecipients() as $recipient) {
                $messageToSend = clone $message;
                $messageToSend->setTo($recipient->getEmail());

                $sent += $this->mailer->send($messageToSend, $failedRecipients);
            }
        }

        return $sent;
    }
}
