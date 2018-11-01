<?php

namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\EmailBundle\Manager\TemplateEmailManager;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Sends translated emails based on recipients in a consumer message.
 */
class TemplateEmailMessageSender
{
    /**
     * @var TemplateEmailManager
     */
    private $templateEmailManager;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param TemplateEmailManager $templateEmailManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(TemplateEmailManager $templateEmailManager, DoctrineHelper $doctrineHelper)
    {
        $this->templateEmailManager = $templateEmailManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array $message
     * @param array $failedRecipients
     * @return int
     * @throws \LogicException
     */
    public function sendTranslatedMessage(array $message, array &$failedRecipients = []): int
    {
        if (!$this->isTranslatable($message)) {
            throw new \LogicException(sprintf('The %s message is not translatable', print_r($message, true)));
        }

        return $this->templateEmailManager
            ->sendTemplateEmail(
                From::fromArray($message['sender']),
                [$this->getRecipient($message)],
                new EmailTemplateCriteria($message['template'], $message['template_entity'] ?? null),
                $message['body'],
                $failedRecipients
            );
    }

    /**
     * @param array $message
     * @return bool
     */
    public function isTranslatable(array $message): bool
    {
        if (!isset($message['template'], $message['sender'], $message['body']) || !is_array($message['body'])) {
            return false;
        }

        return null !== $this->getRecipient($message);
    }

    /**
     * @param array $message
     * @return null|EmailHolderInterface
     */
    private function getRecipient(array $message): ?EmailHolderInterface
    {
        if (isset($message['recipientUserId'])) {
            return $this->doctrineHelper->getEntityReference(User::class, $message['recipientUserId']);
        }

        return null;
    }
}
