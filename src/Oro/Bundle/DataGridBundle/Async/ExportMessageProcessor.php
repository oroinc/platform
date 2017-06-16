<?php
namespace Oro\Bundle\DataGridBundle\Async;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportConnector;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\NotificationBundle\Async\Topics as EmailTopics;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

class ExportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const TEMPLATE_EXPORT_RESULT = 'datagrid_export_result';

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
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EmailRenderer
     */
    private $renderer;

    /**
     * @var TokenSerializerInterface
     */
    private $tokenSerializer;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
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
     * @param EmailRenderer $renderer
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
        LoggerInterface $logger,
        EmailRenderer $renderer
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
        $this->renderer = $renderer;
    }

    /**
     * @param TokenSerializerInterface $tokenSerializer
     */
    public function setTokenSerializer(TokenSerializerInterface $tokenSerializer)
    {
        $this->tokenSerializer = $tokenSerializer;
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
            'securityToken' => null
        ], $body);

        if (! isset($body['securityToken'], $body['parameters']['gridName'], $body['format'])) {
            $this->logger->critical(
                sprintf('[DataGridExportMessageProcessor] Got invalid message: "%s"', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $writer = $this->writerChain->getWriter($body['format']);
        if (! $writer instanceof FileStreamWriter) {
            $this->logger->critical(
                sprintf('[DataGridExportMessageProcessor] Invalid format: "%s"', $body['format']),
                ['message' => $message]
            );

            return self::REJECT;
        }

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
            function () use ($body, $jobUniqueName, $writer) {
                // authenticate the user
                if (!$this->setSecurityToken($body['securityToken'])) {
                    return false;
                }

                $exportResult = $this->exportHandler->handle(
                    $this->exportConnector,
                    $this->exportProcessor,
                    $writer,
                    $body['parameters'],
                    $body['batchSize'],
                    $body['format']
                );

                $this->logger->info(sprintf(
                    '[DataGridExportMessageProcessor] Export result. Success: %s.',
                    $exportResult['success']
                ));

                $this->sendNotificationMessage(
                    $jobUniqueName,
                    $exportResult,
                    $this->tokenStorage->getToken()->getUser()
                );

                // remove the token what was set before message processing
                $this->tokenStorage->setToken();

                return $exportResult['success'];
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param User $user
     * @deprecated Use setSecurityToken instead
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
     * Deserialize the token from the string and sets it to the token storage
     *
     * @param string $serializedToken
     *
     * @return bool
     */
    protected function setSecurityToken($serializedToken)
    {
        $token = $this->tokenSerializer->deserialize($serializedToken);

        if (null === $token) {
            return false;
        }

        $this->tokenStorage->setToken($token);

        return true;
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
        $emailTemplate = $this->findEmailTemplateByName(self::TEMPLATE_EXPORT_RESULT);

        list($subject, $body) = $this->renderer->compileMessage(
            $emailTemplate,
            ['exportResult' => $exportResult, 'jobName' => $jobUniqueName, ]
        );

        $this->producer->send(EmailTopics::SEND_NOTIFICATION_EMAIL, [
            'fromEmail' => $this->configManager->get('oro_notification.email_notification_sender_email'),
            'fromName' => $this->configManager->get('oro_notification.email_notification_sender_name'),
            'toEmail' => $user->getEmail(),
            'subject' => $subject,
            'body' => $body,
            'contentType' => 'text/html',
        ]);
    }

    /**
     * @param string $emailTemplateName
     *
     * @return null|EmailTemplateInterface
     */
    protected function findEmailTemplateByName($emailTemplateName)
    {
        return $this->doctrineHelper
            ->getEntityRepository(EmailTemplate::class)
            ->findOneBy(['name' => $emailTemplateName]);
    }

    /**
     * @return array
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXPORT];
    }
}
