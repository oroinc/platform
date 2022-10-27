<?php

namespace Oro\Bundle\NotificationBundle\Async;

use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesInSymfonyEmailHandler;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Sends email notification.
 */
class SendEmailNotificationProcessor implements MessageProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private MailerInterface $mailer;

    private EmbeddedImagesInSymfonyEmailHandler $embeddedImagesInSymfonyEmailHandler;

    public function __construct(
        MailerInterface $mailer,
        EmbeddedImagesInSymfonyEmailHandler $embeddedImagesInSymfonyEmailHandler
    ) {
        $this->mailer = $mailer;
        $this->embeddedImagesInSymfonyEmailHandler = $embeddedImagesInSymfonyEmailHandler;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $symfonyEmail = $this->createEmailMessage($message->getBody());

        if (!$symfonyEmail) {
            return self::REJECT;
        }

        $sentCount = $this->sendEmailMessage($symfonyEmail);

        if (!$sentCount) {
            return self::REJECT;
        }

        return self::ACK;
    }

    private function createEmailMessage(array $messageBody): ?SymfonyEmail
    {
        try {
            $symfonyEmail = (new SymfonyEmail())
                ->from($messageBody['from'])
                ->to($messageBody['toEmail'])
                ->subject($messageBody['subject']);

            if ($messageBody['contentType'] === 'text/html') {
                $symfonyEmail->html($messageBody['body']);

                $this->embeddedImagesInSymfonyEmailHandler->handleEmbeddedImages($symfonyEmail);
            } else {
                $symfonyEmail->text($messageBody['body']);
            }

            return $symfonyEmail;
        } catch (\InvalidArgumentException $exception) {
            $this->logException($exception, [$messageBody['toEmail']]);
            return null;
        }
    }

    private function sendEmailMessage(SymfonyEmail $symfonyEmail): int
    {
        try {
            $this->mailer->send($symfonyEmail);
            $sentCount = 1;
        } catch (\RuntimeException $exception) {
            $sentCount = 0;
            $recipients = array_map(
                static fn (SymfonyAddress $address) => $address->getAddress(),
                Envelope::create($symfonyEmail)->getRecipients()
            );
            $this->logException($exception, $recipients);
        }

        return $sentCount;
    }

    private function logException(\Exception $exception, array $recipients): void
    {
        $this->logger->error(
            sprintf(
                'Failed to send an email notification to %s: %s',
                implode(', ', $recipients),
                $exception->getMessage()
            ),
            ['exception' => $exception]
        );
    }
}
