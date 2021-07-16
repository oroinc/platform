<?php

namespace Oro\Bundle\EmailBundle\Util;

use Monolog\Logger;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Sends message using Swift_Mailer::send internally
 */
class MailerWrapper extends \Swift_Mailer
{
    /**
     * @var ServiceLink
     */
    protected $loggerLink;

    /** @var \Swift_Mailer */
    private $mailer;

    public function __construct(\Swift_Transport $transport, ServiceLink $loggerLink)
    {
        /* we need _mailer because _transport is private
           (not protected) in Swift_Mailer, unfortunately... */
        $this->mailer       = parent::newInstance($transport);
        $this->loggerLink   = $loggerLink;
    }

    /**
     * @return Logger
     */
    protected function getLogger()
    {
        return $this->loggerLink->getService();
    }

    /**
     * @param \Swift_Mime_SimpleMessage $message
     * @param array|null $failedRecipients
     * @return int
     *
     * @throws \Swift_TransportException
     */
    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        try {
            $result = $this->mailer->send($message, $failedRecipients);
        } catch (\Swift_TransportException $transportException) {
            if (is_array($failedRecipients)) {
                $failedRecipients = implode(',', $failedRecipients);
            }
            $logger = $this->getLogger();

            $logger->crit(sprintf("Mail message: %s", $message));
            $logger->crit(sprintf("Mail recipients: %s", $failedRecipients));
            $logger->crit(
                sprintf("Error message: %s", $transportException->getMessage()),
                ['exception' => $transportException]
            );

            throw $transportException;
        }

        return $result;
    }

    /**
     * @param \Swift_Transport $transport
     * @return MailerWrapper
     */
    public static function newInstance(\Swift_Transport $transport)
    {
        return new self($transport);
    }

    /**
     * @return \Swift_Transport
     */
    public function getTransport()
    {
        return $this->mailer->getTransport();
    }

    public function registerPlugin(\Swift_Events_EventListener $plugin)
    {
        $this->getTransport()->registerPlugin($plugin);
    }
}
