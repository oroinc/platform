<?php

namespace Oro\Bundle\NotificationBundle\Async;

use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesInSymfonyEmailHandler;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Sends email notification.
 */
class SendEmailNotificationProcessor implements MessageProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private MailerInterface $mailer;

    private EmbeddedImagesInSymfonyEmailHandler $embeddedImagesInSymfonyEmailHandler;

    private ValidatorInterface $validator;

    public function __construct(
        MailerInterface $mailer,
        EmbeddedImagesInSymfonyEmailHandler $embeddedImagesInSymfonyEmailHandler,
        ValidatorInterface $validator
    ) {
        $this->mailer = $mailer;
        $this->embeddedImagesInSymfonyEmailHandler = $embeddedImagesInSymfonyEmailHandler;
        $this->validator = $validator;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $this->getMessageBody($message);
        if (!$messageBody) {
            return MessageProcessorInterface::REJECT;
        }

        $symfonyEmail = $this->createEmailMessage($messageBody);
        $sentCount = $this->sendEmailMessage($symfonyEmail);

        if (!$sentCount) {
            return self::REJECT;
        }

        return self::ACK;
    }

    private function getMessageBody(MessageInterface $message): array
    {
        $messageBody = array_merge(
            [
                'from' => null,
                'toEmail' => null,
                'subject' => null,
                'body' => null,
                'contentType' => null,
            ],
            JSON::decode($message->getBody())
        );

        if (empty($messageBody['from']) || empty($messageBody['toEmail']) || empty($messageBody['subject'])
            || empty($messageBody['body'])) {
            $this->logger->critical(
                sprintf(
                    'Message properties %s were not expected to be empty',
                    implode(', ', ['from', 'toEmail', 'subject', 'body'])
                )
            );

            return [];
        }

        if (!$this->validateAddress($messageBody['from'])) {
            $this->logger->error(sprintf('Email address "%s" is not valid', $messageBody['from']));

            return [];
        }

        if (!$this->validateAddress($messageBody['toEmail'])) {
            $this->logger->error(sprintf('Email address "%s" is not valid', $messageBody['toEmail']));

            return [];
        }

        return $messageBody;
    }

    private function createEmailMessage(array $messageBody): SymfonyEmail
    {
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
            $this->logger->error(
                sprintf(
                    'Failed to send an email notification to %s: %s',
                    implode(', ', $recipients),
                    $exception->getMessage()
                ),
                ['exception' => $exception]
            );
        }

        return $sentCount;
    }

    private function validateAddress(?string $email): bool
    {
        static $emailConstraint;
        if (!$emailConstraint) {
            $emailConstraint = new EmailConstraint();
        }

        $errorList = $this->validator->validate($email, $emailConstraint);

        return !$errorList->count();
    }
}
