<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Mock\Mailer;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;

/**
 * Mailer for behat tests
 */
class DirectMailerDecorator extends DirectMailer
{
    /** @var DirectMailer */
    private $directMailer;

    /** @var CacheProvider */
    private $cache;

    /**
     * @param DirectMailer $directMailer
     * @param CacheProvider $cache
     */
    public function __construct(DirectMailer $directMailer, CacheProvider $cache)
    {
        $this->directMailer = $directMailer;
        $this->cache = $cache;
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
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $messages = $this->getSentMessages();
        $messages[] = $message;

        $this->cache->save('messages', serialize($messages));

        return $this->directMailer->send($message, $failedRecipients);
    }

    public function clear()
    {
        $this->cache->save('messages', serialize([]));
    }

    /**
     * @return array
     */
    public function getSentMessages()
    {
        if (!$this->cache->contains('messages')) {
            return [];
        }

        $messages = unserialize($this->cache->fetch('messages'));
        if (!is_array($messages)) {
            return [];
        }

        return $messages;
    }
}
