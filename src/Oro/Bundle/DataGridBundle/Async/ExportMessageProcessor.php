<?php
namespace Oro\Bundle\DataGridBundle\Async;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;

use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportConnector;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\NotificationBundle\Async\Topics as EmailTopics;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ExportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ExportHandler
     */
    private $exportHandler;

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
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var DatagridExportConnector
     */
    private $exportConnector;

    /**
     * @var ExportProcessor
     */
    private $exportProcessor;

    /**
     * @var WriterChain
     */
    private $writerChain;

    /**
     * @var FileStreamWriter
     */
    private $exportWriter;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ExportHandler $exportHandler
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     * @param DatagridExportConnector $exportConnector
     * @param ExportProcessor $exportProcessor
     * @param WriterChain $writerChain
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface $logger
     */
    public function __construct(
        ExportHandler $exportHandler,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        DatagridExportConnector $exportConnector,
        ExportProcessor $exportProcessor,
        WriterChain $writerChain,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger
    ) {
        $this->exportHandler = $exportHandler;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->exportConnector = $exportConnector;
        $this->exportProcessor = $exportProcessor;
        $this->writerChain = $writerChain;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    /**
     * @param FileStreamWriter $exportWriter
     */
    public function setWriter(FileStreamWriter $exportWriter)
    {
        $this->exportWriter = $exportWriter;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'format' => null,
            'batchSize' => 200,
            'parameters' => [
                'gridName' => null,
                'gridParameters' => [],
                FormatterProvider::FORMAT_TYPE => 'excel',
            ],
            'userId' => null,
        ], $body);

        if (! is_array($body['parameters'])
                || ! isset($body['userId'], $body['parameters'], $body['parameters']['gridName'], $body['format'])) {
            $this->logger->critical(
                sprintf('[DataGridExportMessageProcessor] Got invalid message: "%s"', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $writer = $this->writerChain->getWriter($body['format']);
        if (!$writer || ! $writer instanceof FileStreamWriter) {
            $this->logger->critical(
                sprintf('[DataGridExportMessageProcessor] Invalid writer alias: "%s"', $body['format']),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $this->exportWriter = $writer;

        /** @var User $user */
        $user = $this->doctrineHelper->getEntityRepository(User::class)->find($body['userId']);
        if (! $user) {
            $this->logger->critical(
                sprintf('[DataGridExportMessageProcessor] Cannot find user by id "%s"', $body['userId']),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $this->authorizeUser($user);

        $contextParameters = new ParameterBag($body['parameters']['gridParameters']);
        $contextParameters->set(ActionExtension::ENABLE_ACTIONS_PARAMETER, false);
        $body['parameters']['gridParameters'] = $contextParameters;

        $jobUniqueName = sprintf(
            'datagrid_export_%s_%s',
            $body['parameters']['gridName'],
            $body['format']
        );

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobUniqueName,
            function () use ($body, $jobUniqueName, $user) {
                $exportResult = $this->exportHandler->handle(
                    $this->exportConnector,
                    $this->exportProcessor,
                    $this->exportWriter,
                    $body['parameters'],
                    $body['batchSize'],
                    $body['format']
                );

                $this->logger->info(sprintf(
                    '[DataGridExportMessageProcessor] Export result. Success: %s.',
                    $exportResult['success']
                ));

                $this->sendNotificationMessage($jobUniqueName, $exportResult, $user);

                return $exportResult['success'];
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param User $user
     */
    protected function authorizeUser(User $user)
    {
        $token = new UsernamePasswordOrganizationToken(
            $user,
            null,
            'main',
            $user->getOrganization(),
            $user->getRoles()
        );

        $this->tokenStorage->setToken($token);
    }

    /**
     * Send async email notification message with export result.
     *
     * @param string $jobUniqueName
     * @param array $exportResult
     * @param User $user
     */
    protected function sendNotificationMessage($jobUniqueName, array $exportResult, User $user)
    {
        $subject = sprintf('Grid export result for job %s', $jobUniqueName);

        if ($exportResult['success']) {
            $body = sprintf('Grid export performed successfully. Download link: %s', $exportResult['url']);
        } else {
            $body = 'Grid export operation fails.';
        }

        $this->producer->send(EmailTopics::SEND_NOTIFICATION_EMAIL, [
            'fromEmail' => $this->configManager->get('oro_notification.email_notification_sender_email'),
            'fromName' => $this->configManager->get('oro_notification.email_notification_sender_name'),
            'toEmail' => $user->getEmail(),
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    /**
     * @return array
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXPORT];
    }
}
