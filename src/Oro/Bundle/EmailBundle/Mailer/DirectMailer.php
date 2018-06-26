<?php

namespace Oro\Bundle\EmailBundle\Mailer;

use Monolog\Logger;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Event\SendEmailTransport;
use Oro\Bundle\EmailBundle\Exception\NotSupportedException;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The goal of this class is to send an email directly, not using a mail spool
 * even when it is configured for a base mailer
 */
class DirectMailer extends \Swift_Mailer
{
    /** @var \Swift_Mailer */
    protected $baseMailer;

    /** @var \Swift_SmtpTransport */
    protected $smtpTransport;

    /** @var ContainerInterface */
    protected $container;

    /** @var ServiceLink  */
    protected $loggerLink;

    /** @var \Swift_Transport */
    private $transport;

    /**
     * Constructor
     *
     * @param \Swift_Mailer      $baseMailer
     * @param ContainerInterface $container
     */
    public function __construct(
        \Swift_Mailer $baseMailer,
        ContainerInterface $container
    ) {
        $this->baseMailer = $baseMailer;
        $this->container  = $container;
    }

    /**
     * @param ServiceLink $loggerLink
     */
    public function setLogger(ServiceLink $loggerLink)
    {
        $this->loggerLink = $loggerLink;
    }

    /**
     * @return Logger
     */
    protected function getLogger()
    {
        return $this->loggerLink->getService();
    }

    /**
     * Set SmtpTransport instance or create a new if default mailer transport is not smtp
     *
     * @param EmailOrigin $emailOrigin
     */
    public function prepareSmtpTransport($emailOrigin)
    {
        if ($emailOrigin instanceof UserEmailOrigin) {
            /* Modify transport smtp settings */
            if ($emailOrigin->isSmtpConfigured()) {
                $this->prepareEmailOriginSmtpTransport($emailOrigin);
            }
        }

        $this->afterPrepareSmtpTransport();
    }

    /**
     * @param EmailOrigin $emailOrigin
     */
    public function prepareEmailOriginSmtpTransport($emailOrigin)
    {
        if (!$this->smtpTransport) {
            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $this->container->get('event_dispatcher');
            $event = $eventDispatcher->dispatch(
                SendEmailTransport::NAME,
                new SendEmailTransport($emailOrigin, $this->getTransport())
            );
            $this->smtpTransport = $event->getTransport();

            if ($this->smtpTransport instanceof \Swift_Transport_AbstractSmtpTransport) {
                $this->configureTransportLocalDomain($this->smtpTransport);
            }
        }
    }

    /**
     * Last chance to modify SMTP transport
     *
     * @param SmtpSettings|null $smtpSettings
     */
    public function afterPrepareSmtpTransport(SmtpSettings $smtpSettings = null)
    {
        if ($this->smtpTransport) {
            return;
        }

        if (!$smtpSettings instanceof SmtpSettings) {
            $provider = $this->container->get('oro_email.provider.smtp_settings');
            $smtpSettings = $provider->getSmtpSettings();
        }

        if (!$smtpSettings->isEligible()) {
            return;
        }

        $transport = $this->getTransport();
        $host = $smtpSettings->getHost();
        $port = $smtpSettings->getPort();
        $encryption = $smtpSettings->getEncryption();

        if ($transport instanceof \Swift_Transport_EsmtpTransport) {
            $transport->setHost($host);
            $transport->setPort($port);
            $transport->setEncryption($encryption);
        } else {
            $transport = \Swift_SmtpTransport::newInstance($host, $port, $encryption);
        }

        $transport
            ->setUsername($smtpSettings->getUsername())
            ->setPassword($smtpSettings->getPassword())
        ;

        $this->smtpTransport = $transport;
        $this->configureTransportLocalDomain($transport);
    }

    /**
     * The Transport used to send messages.
     *
     * @return \Swift_Transport|\Swift_SmtpTransport
     */
    public function getTransport()
    {
        if ($this->smtpTransport) {
            return $this->smtpTransport;
        }

        if (!$this->transport) {
            $transport = $this->baseMailer->getTransport();
            if ($transport instanceof \Swift_Transport_SpoolTransport) {
                $transport = $this->findRealTransport();
                if (!$transport) {
                    $transport = \Swift_NullTransport::newInstance();
                }
            }

            $this->transport = $transport;

            // replacing the original transport with SMTP transport
            // which configured with parameters from the System Configuration -> SMTP Settings
            $this->prepareSmtpTransport(null);
            if ($this->smtpTransport) {
                $this->transport = $this->smtpTransport;
            }

            if ($this->transport instanceof \Swift_Transport_EsmtpTransport) {
                $this->addXOAuth2Authenticator($this->transport);
            }

            if ($this->transport instanceof \Swift_Transport_AbstractSmtpTransport) {
                $this->configureTransportLocalDomain($this->transport);
            }
        }

        return $this->transport;
    }

    /**
     * Register a plugin using a known unique key (e.g. myPlugin).
     *
     * @param \Swift_Events_EventListener $plugin
     * @throws \Oro\Bundle\EmailBundle\Exception\NotSupportedException
     */
    public function registerPlugin(\Swift_Events_EventListener $plugin)
    {
        throw new NotSupportedException('The registerPlugin() is not supported for this mailer.');
    }

    /**
     * Sends the given message.
     *
     * The return value is the number of recipients who were accepted for
     * delivery.
     *
     * @param \Swift_Mime_Message $message
     * @param array               $failedRecipients An array of failures by-reference
     *
     * @return int The number of recipients who were accepted for delivery
     * @throws \Exception
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $result = 0;
        // start a transport if needed
        $needToStopRealTransport = false;
        if (!$this->getTransport()->isStarted()) {
            $this->getTransport()->start();
            $needToStopRealTransport = true;
        }
        // send a mail
        $sendException = null;
        try {
            if ($this->smtpTransport) {
                $result = $this->smtpTransport->send($message, $failedRecipients);
            } else {
                $mailerInstance = new \Swift_Mailer($this->getTransport());
                $result = $mailerInstance->send($message, $failedRecipients);
            }
        } catch (\Swift_TransportException $transportException) {
            $logger = $this->getLogger();

            $logger->crit(sprintf("Mail message: %s", $message));
            $logger->crit(sprintf("Mail recipients: %s", implode(',', $failedRecipients)));
            $logger->crit(
                sprintf("Error message: %s", $transportException->getMessage()),
                ['exception' => $transportException]
            );

            $sendException = $transportException;
        } catch (\Exception $unexpectedEx) {
            $sendException = $unexpectedEx;
        }
        // stop a transport if it was started before
        if ($needToStopRealTransport) {
            try {
                $this->getTransport()->stop();
            } catch (\Exception $ex) {
                // ignore errors here
            }
        }
        // rethrow send failure
        if ($sendException) {
            throw $sendException;
        }

        return $result;
    }

    /**
     * Returns a real transport used to send mails by a mailer specified in the constructor of this class
     *
     * @return \Swift_Transport|null
     */
    protected function findRealTransport()
    {
        $realTransport = null;
        $mailers       = array_keys($this->container->getParameter('swiftmailer.mailers'));
        foreach ($mailers as $name) {
            if (!$this->container->initialized(sprintf('swiftmailer.mailer.%s', $name))
            ) {
                continue;
            }
            $mailer = $this->container->get(sprintf('swiftmailer.mailer.%s', $name));
            if ($mailer === $this->baseMailer) {
                $realTransport = $this->container->get(sprintf('swiftmailer.mailer.%s.transport.real', $name));
                break;
            }
        }

        return $realTransport;
    }

    /**
     * @param \Swift_Transport_EsmtpTransport $transport
     *
     * @return DirectMailer
     */
    protected function addXOAuth2Authenticator($transport)
    {
        $handlers = $transport->getExtensionHandlers();
        $handlers = is_array($handlers) ? $handlers : [];

        foreach ($handlers as $handler) {
            if ($handler instanceof \Swift_Transport_Esmtp_AuthHandler) {
                $authenticators = $handler->getAuthenticators();
                $isOAuth2Exist = false;
                foreach ($authenticators as $authenticator) {
                    if ($authenticator instanceof \Swift_Transport_Esmtp_Auth_XOAuth2Authenticator) {
                        $isOAuth2Exist = true;
                    }
                }

                if (!$isOAuth2Exist) {
                    $authenticators[] = new \Swift_Transport_Esmtp_Auth_XOAuth2Authenticator();
                    $handler->setAuthenticators($authenticators);
                }
            }
        }

        return $this;
    }

    /**
     * @param  \Swift_Transport_AbstractSmtpTransport $transport
     */
    protected function configureTransportLocalDomain(\Swift_Transport_AbstractSmtpTransport $transport)
    {
        if (php_sapi_name() === 'cli') {
            return;
        }
        $host = $this->container->get('request_stack')->getCurrentRequest()->server->get('HTTP_HOST');
        // fix local domain when wild-card vhost is used and auto-detection fails
        if (0 === strpos($transport->getLocalDomain(), '*') && !empty($host)) {
            $transport->setLocalDomain($host);
        }
    }
}
