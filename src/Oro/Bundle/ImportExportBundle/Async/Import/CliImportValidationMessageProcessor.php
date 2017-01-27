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

class CliImportValidationMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
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

    /**
     * @var LoggerInterface
     */
    private $router;

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
            'jobName' => JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV,
            'processorAlias' => null,
            'options' => []
        ], $body);

        if (! $body['processorAlias'] || ! $body['fileName'] || ! $body['notifyEmail']) {
            $this->logger->critical(
                sprintf('Invalid message'),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            sprintf('oro:import_validation:cli:%s:%s', $body['processorAlias'], $message->getMessageId()),
            function () use ($body) {
                $this->cliImportHandler->setImportingFileName($body['fileName']);

                $result = $this->cliImportHandler->handleImportValidation(
                    $body['jobName'],
                    $body['processorAlias'],
                    $body['options']
                );

                $summary = sprintf(
                    'Import validation from file %s was completed for Entity "%s",
                    processed: %s, read: %d, errors: %d.',
                    basename($body['fileName']),
                    $result['entityName'],
                    $result['counts']['process'],
                    $result['counts']['read'],
                    $result['errors']
                );

                $this->logger->info($summary);

                $fromEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
                $fromName = $this->configManager->get('oro_notification.email_notification_sender_name');
                if ($result['counts']['errors']) {
                    $url = $this->configManager->get('oro_ui.application_url') . $this->router->generate(
                        'oro_importexport_error_log',
                        ['jobCode' => $result['jobCode']]
                    );
                    $downloadLink = sprintf('<br><a target="_blank" href="%s">Download</a>', $url);
                    $summary .= $downloadLink;
                }

                $this->producer->send(
                    NotificationTopics::SEND_NOTIFICATION_EMAIL,
                    [
                        'fromEmail' => $fromEmail,
                        'fromName' => $fromName,
                        'toEmail' => $body['notifyEmail'],
                        'subject' => sprintf('Result of import validation file %s', basename($body['fileName'])),
                        'body' => $summary,
                        'contentType' => 'text/html'
                    ]
                );

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
        return [Topics::IMPORT_CLI_VALIDATION];
    }
}
