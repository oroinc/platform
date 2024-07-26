<?php

namespace Oro\Bundle\EmailBundle\Sender;

use Oro\Bundle\EmailBundle\Builder\EmailUserFromEmailModelBuilder;
use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesInEmailModelHandler;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\EventListener\EntityListener;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Mailer\Envelope\EmailOriginAwareEnvelope;
use Oro\Bundle\EmailBundle\Tools\MessageIdHelper;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Sends email using data from {@see EmailModel}.
 * Creates {@see EmailUser} entity.
 */
class EmailModelSender
{
    protected const DOCTYPE_REGEX = '/^<!DOCTYPE.*>/i';
    protected const DOCTYPE = '<!DOCTYPE HTML>';

    private MailerInterface $mailer;
    private EmbeddedImagesInEmailModelHandler $embeddedImagesInEmailModelHandler;
    private EmailFactory $symfonyEmailFactory;
    private EmailUserFromEmailModelBuilder $emailUserFromEmailModelBuilder;
    private EventDispatcherInterface $eventDispatcher;
    private EntityListener $emailEntityListener;

    public function __construct(
        MailerInterface $mailer,
        EmbeddedImagesInEmailModelHandler $embeddedImagesInEmailModelHandler,
        EmailFactory $symfonyEmailFactory,
        EmailUserFromEmailModelBuilder $emailUserFromEmailModelBuilder,
        EventDispatcherInterface $eventDispatcher,
        EntityListener $emailEntityListener
    ) {
        $this->mailer = $mailer;
        $this->embeddedImagesInEmailModelHandler = $embeddedImagesInEmailModelHandler;
        $this->symfonyEmailFactory = $symfonyEmailFactory;
        $this->emailUserFromEmailModelBuilder = $emailUserFromEmailModelBuilder;
        $this->eventDispatcher = $eventDispatcher;
        $this->emailEntityListener = $emailEntityListener;
    }

    /**
     * Sends email using data from {@see EmailModel}.
     * Creates {@see EmailUser} entity.
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function send(EmailModel $emailModel, EmailOrigin $emailOrigin = null, bool $persist = true): EmailUser
    {
        if ($emailModel->getType() === 'html') {
            // Extracts embedded images from email body and adds them as attachments.
            $this->embeddedImagesInEmailModelHandler->handleEmbeddedImages($emailModel);
        }

        $this->handleDoctype($emailModel);

        $symfonyEmail = $this->symfonyEmailFactory->createFromEmailModel($emailModel);

        if ($emailOrigin) {
            $emailOriginAwareEnvelope = EmailOriginAwareEnvelope::create($symfonyEmail);
            $emailOriginAwareEnvelope->setEmailOrigin($emailOrigin);
        }

        $this->mailer->send($symfonyEmail, $emailOriginAwareEnvelope ?? null);

        return $this->createEmailUser(
            $emailModel,
            MessageIdHelper::getMessageId($symfonyEmail),
            $symfonyEmail->getDate(),
            $emailOrigin,
            $persist
        );
    }

    private function createEmailUser(
        EmailModel $emailModel,
        string $messageId,
        \DateTimeInterface $sentAt,
        ?EmailOrigin $emailOrigin,
        bool $persist
    ): EmailUser {
        $emailUser = $this->emailUserFromEmailModelBuilder->createFromEmailModel($emailModel, $messageId, $sentAt);

        if ($emailOrigin) {
            $this->emailUserFromEmailModelBuilder->setEmailOrigin($emailUser, $emailOrigin);
        }

        if ($persist) {
            $this->emailUserFromEmailModelBuilder->addActivityEntities($emailUser, $emailModel->getContexts());
            if (!$emailModel->isUpdateEmptyContextsAllowed()) {
                $this->emailEntityListener->skipUpdateActivities($emailUser->getEmail());
            }
            $this->emailUserFromEmailModelBuilder->persistAndFlush();
        }

        $event = new EmailBodyAdded($emailUser->getEmail());
        $this->eventDispatcher->dispatch($event, EmailBodyAdded::NAME);

        return $emailUser;
    }

    /**
     * Adds DOCTYPE if not specified
     *
     * @param EmailModel $emailModel
     *
     * @return void
     */
    protected function handleDoctype(EmailModel $emailModel): void
    {
        if ($emailModel->getType() !== 'html') {
            return;
        }

        $content = $emailModel->getBody();
        if ($content && !preg_match(self::DOCTYPE_REGEX, trim($content))) {
            $emailModel->setBody(self::DOCTYPE . $content);
        }
    }
}
