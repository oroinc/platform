<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Mock\Mailer;

use Doctrine\DBAL\Connection;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\PdoAdapter;

/**
 * Mailer for behat tests
 */
class DirectMailerDecorator extends DirectMailer
{
    /** @var DirectMailer */
    private $directMailer;

    /** @var PdoAdapter */
    private $cache;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(DirectMailer $directMailer, PdoAdapter $cache, DoctrineHelper $doctrineHelper)
    {
        $this->directMailer = $directMailer;
        $this->cache = $cache;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareSmtpTransport($emailOrigin)
    {
        $this->directMailer->prepareSmtpTransport($emailOrigin);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareEmailOriginSmtpTransport($emailOrigin)
    {
        $this->directMailer->prepareEmailOriginSmtpTransport($emailOrigin);
    }

    /**
     * {@inheritdoc}
     */
    public function afterPrepareSmtpTransport(SmtpSettings $smtpSettings = null)
    {
        $this->directMailer->afterPrepareSmtpTransport($smtpSettings);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransport()
    {
        return $this->directMailer->getTransport();
    }

    /**
     * {@inheritdoc}
     */
    public function registerPlugin(\Swift_Events_EventListener $plugin)
    {
        $this->directMailer->registerPlugin($plugin);
    }

    /**
     * {@inheritdoc}
     */
    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        /** @var CacheItemInterface $item */
        $item = $this->cache->getItem(uniqid('', true));
        $item->set(serialize($message));
        $this->cache->save($item);

        return $this->directMailer->send($message, $failedRecipients);
    }

    public function clear()
    {
        $this->cache->clear();
    }

    public function getSentMessages(): array
    {
        /** @var Connection $connection */
        $connection = $this->doctrineHelper->getManager()->getConnection('message_queue');

        // guard
        $this->cache->getItem('oro_behat_email');

        $messageIds = array_column($connection->fetchAll('SELECT item_id FROM oro_behat_email'), 'item_id');

        $messages = [];
        foreach ($messageIds as $messageId) {
            $messages[] = unserialize($this->cache->getItem($messageId)->get());
        }

        return $messages;
    }
}
