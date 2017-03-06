<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Psr\Log\LoggerInterface;

use Symfony\Component\Routing\Router;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\CliImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class CliImportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var CliImportHandler
     */
    private $cliImportHandler;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CliImportHandler $cliImportHandler,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        ConfigManager $configManager,
        LoggerInterface $logger,
        Router $router
    ) {
        $this->cliImportHandler = $cliImportHandler;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->configManager = $configManager;
        $this->logger = $logger;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'fileName' => null,
            'notifyEmail' => null,
            'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
            'processorAlias' => null,
            'options' => []
        ], $body);


        if (! $body['processorAlias'] || ! $body['fileName']) {
            $this->logger->critical(
                sprintf('Invalid message'),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            sprintf('oro:import:cli:%s:%s', $body['processorAlias'], $message->getMessageId()),
            function () use ($body) {
                $this->cliImportHandler->setImportingFileName($body['fileName']);

                $result = $this->cliImportHandler->handleImport(
                    $body['jobName'],
                    $body['processorAlias'],
                    $body['options']
                );

                $summary = sprintf(
                    'Import from file %s was completed,
                    processed: %s, read: %d, errors: %d, added: %d, replaced: %d: ',
                    basename($body['fileName']),
                    $result['counts']['process'],
                    $result['counts']['read'],
                    $result['errors'],
                    $result['counts']['add'],
                    $result['counts']['replace']
                );

                $this->logger->info($summary);

                if ($body['notifyEmail']) {
                    if ($result['errors']) {
                        $url = $this->configManager->get('oro_ui.application_url') . $this->router->generate(
                            'oro_importexport_error_log',
                            ['jobCode' => $result['jobCode']]
                        );
                        $downloadLink = sprintf('<br><a href="%s" target="_blank">Download</a>', $url);
                        $summary .= $downloadLink;
                    }

                    $fromEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
                    $fromName = $this->configManager->get('oro_notification.email_notification_sender_name');
                    $this->producer->send(
                        NotificationTopics::SEND_NOTIFICATION_EMAIL,
                        [
                            'fromEmail' => $fromEmail,
                            'fromName' => $fromName,
                            'toEmail' => $body['notifyEmail'],
                            'subject' => sprintf('Result of import file %s', basename($body['fileName'])),
                            'body' => $summary
                        ]
                    );
                }

                return $result['success'];
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::IMPORT_CLI];
    }
}
