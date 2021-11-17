<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesInSymfonyEmailHandler;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Responsible for sending email templates in preferred recipient's language when recipient entities given or in
 * a specific language to a set of email addresses.
 */
class EmailTemplateManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private MailerInterface $mailer;

    private EmbeddedImagesInSymfonyEmailHandler $embeddedImagesHandler;

    private LocalizedTemplateProvider $localizedTemplateProvider;

    public function __construct(
        MailerInterface $mailer,
        EmbeddedImagesInSymfonyEmailHandler $embeddedImagesInSymfonyEmailHandler,
        LocalizedTemplateProvider $localizedTemplateProvider
    ) {
        $this->mailer = $mailer;
        $this->embeddedImagesHandler = $embeddedImagesInSymfonyEmailHandler;
        $this->localizedTemplateProvider = $localizedTemplateProvider;
        $this->logger = new NullLogger();
    }

    /**
     * @param From $from
     * @param iterable<EmailHolderInterface> $recipients
     * @param EmailTemplateCriteria $criteria
     * @param array $templateParams
     * @return int
     */
    public function sendTemplateEmail(
        From $from,
        iterable $recipients,
        EmailTemplateCriteria $criteria,
        array $templateParams = []
    ): int {
        $sent = 0;
        $templateCollection = $this->localizedTemplateProvider->getAggregated($recipients, $criteria, $templateParams);

        foreach ($templateCollection as $localizedTemplateDTO) {
            $emailTemplate = $localizedTemplateDTO->getEmailTemplate();

            $symfonyEmail = (new SymfonyEmail())
                ->from($from->toString())
                ->subject($emailTemplate->getSubject());

            if ($emailTemplate->getType() === EmailTemplate::CONTENT_TYPE_HTML) {
                $symfonyEmail->html($emailTemplate->getContent());

                $this->embeddedImagesHandler->handleEmbeddedImages($symfonyEmail);
            } else {
                $symfonyEmail->text($emailTemplate->getContent());
            }

            foreach ($localizedTemplateDTO->getRecipients() as $recipient) {
                $messageToSend = clone $symfonyEmail;
                $messageToSend->to($recipient->getEmail());

                try {
                    $this->mailer->send($messageToSend);
                    $sent++;
                } catch (\RuntimeException $exception) {
                    $this->logger->error(
                        sprintf(
                            'Failed to send an email to "%s" using "%s" email template: %s',
                            $recipient->getEmail(),
                            $criteria->getName(),
                            $exception->getMessage()
                        ),
                        ['exception' => $exception, 'criteria' => $criteria]
                    );
                }
            }
        }

        return $sent;
    }
}
