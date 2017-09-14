<?php

namespace Oro\Bundle\ImportExportBundle\Async;

use Oro\Bundle\UserBundle\Entity\UserInterface;
use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NotificationBundle\Async\Topics as EmailTopics;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

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
     * @var SecurityFacade
     */
    private $securityFacade;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TokenSerializerInterface
     */
    protected $tokenSerializer;

    /**
     * @var ImportExportJobSummaryResultService
     */
    private $importExportJobSummaryResultService;

    /**
     * @param ExportHandler                       $exportHandler
     * @param JobRunner                           $jobRunner
     * @param MessageProducerInterface            $producer
     * @param ConfigManager                       $configManager
     * @param DoctrineHelper                      $doctrineHelper
     * @param SecurityFacade                      $securityFacade
     * @param TokenStorageInterface               $tokenStorage
     * @param LoggerInterface                     $logger
     * @param ImportExportJobSummaryResultService $importExportJobSummaryResultService
     */
    public function __construct(
        ExportHandler $exportHandler,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        ImportExportJobSummaryResultService $importExportJobSummaryResultService
    ) {
        $this->exportHandler                       = $exportHandler;
        $this->jobRunner                           = $jobRunner;
        $this->producer                            = $producer;
        $this->configManager                       = $configManager;
        $this->doctrineHelper                      = $doctrineHelper;
        $this->securityFacade                      = $securityFacade;
        $this->tokenStorage                        = $tokenStorage;
        $this->logger                              = $logger;
        $this->importExportJobSummaryResultService = $importExportJobSummaryResultService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXPORT];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        if (!($body = $this->validateMessageBody($message))) {
            return self::REJECT;
        }

        $user = $body['user'];
        $result = $this->runUniqueExportJob($message, $body, $user);

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * Send async email notification message with export result.
     *
     * @param string $jobUniqueName
     * @param array  $exportResult
     * @param User   $user
     */
    protected function sendNotificationMessage($jobUniqueName, array $exportResult, $user)
    {
        list($subject, $body) = $this->importExportJobSummaryResultService->processSummaryExportResultForNotification(
            $jobUniqueName,
            $exportResult
        );

        $this->producer->send(EmailTopics::SEND_NOTIFICATION_EMAIL, [
            'fromEmail'   => $this->configManager->get('oro_notification.email_notification_sender_email'),
            'fromName'    => $this->configManager->get('oro_notification.email_notification_sender_name'),
            'toEmail'     => $user->getEmail(),
            'subject'     => $subject,
            'body'        => $body,
            'contentType' => 'text/html',
        ]);
    }

    /**
     * Returns validated array message body if all required parameters set in message body and bool false otherwise.
     *
     * @param MessageInterface $message
     *
     * @return array|false
     *
     * @throws \InvalidArgumentException
     * @throws \Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException
     */
    protected function validateMessageBody(MessageInterface $message)
    {
        $body = $this->getMessageBody($message);
        if (!isset($body['jobName'], $body['processorAlias'], $body['userId'], $body['securityToken'])) {
            $this->logger->critical(
                sprintf('[ExportMessageProcessor] Got invalid message: "%s"', $message->getBody()),
                ['message' => $message]
            );

            return false;
        }
        /** @var User $user */
        $user = $this->doctrineHelper->getEntityRepository(User::class)->find($body['userId']);
        if (!$user) {
            $this->logger->critical(
                sprintf('[ExportMessageProcessor] Cannot find user by id "%s"', $body['userId']),
                ['message' => $message]
            );

            return false;
        }
        $body['user'] = $user;

        $token = $this->getTokenSerializer()->deserialize($body['securityToken']);
        if (!$this->setSecurityToken($token)) {
            $this->logger->critical('[ExportMessageProcessor] Cannot set security token in the token storage');

            return false;
        }

        if (isset($body['organizationId'])) {
            $body['options']['organization'] = $this->doctrineHelper
                ->getEntityRepository(Organization::class)
                ->find($body['organizationId']);
        }

        return $body;
    }

    /**
     * @param TokenSerializerInterface $tokenSerializer
     */
    public function setTokenSerializer(TokenSerializerInterface $tokenSerializer)
    {
        $this->tokenSerializer = $tokenSerializer;
    }

    /**
     * @param TokenInterface|null $token
     *
     * @return bool
     */
    protected function setSecurityToken(TokenInterface $token = null)
    {
        if (null === $token) {
            return false;
        }

        $this->tokenStorage->setToken($token);

        return true;
    }

    /**
     * @return TokenSerializerInterface
     *
     * @throws \Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException
     */
    protected function getTokenSerializer()
    {
        if (null === $this->tokenSerializer) {
            throw new InvalidConfigurationException('Token serializer does not set!');
        }

        return $this->tokenSerializer;
    }

    /**
     * Extract message body as array from $message
     *
     * @param MessageInterface $message
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function getMessageBody(MessageInterface $message)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive(
            [
                'jobName'          => null,
                'processorAlias'   => null,
                'userId'           => null,
                'organizationId'   => null,
                'exportType'       => ProcessorRegistry::TYPE_EXPORT,
                'outputFormat'     => 'csv',
                'outputFilePrefix' => null,
                'options'          => [],
                'securityToken'    => null,
            ],
            $body
        );

        return $body;
    }

    /**
     * @param MessageInterface $message
     * @param array $body
     * @param UserInterface $user
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function runUniqueExportJob(MessageInterface $message, $body, UserInterface $user)
    {
        $jobUniqueName = Topics::EXPORT . '_' . $body['processorAlias'];
        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobUniqueName,
            function () use ($body, $jobUniqueName, $user) {
                $exportResult = $this->exportHandler->getExportResult(
                    $body['jobName'],
                    $body['processorAlias'],
                    $body['exportType'],
                    $body['outputFormat'],
                    $body['outputFilePrefix'],
                    $body['options']
                );

                $this->sendNotificationMessage($jobUniqueName, $exportResult, $user);

                $this->logger->info(
                    sprintf(
                        '[ExportMessageProcessor] Export result. Success: %s. ReadsCount: %s. ErrorsCount: %s',
                        $exportResult['success'],
                        $exportResult['readsCount'],
                        $exportResult['errorsCount']
                    )
                );

                return $exportResult['success'];
            }
        );

        return $result;
    }
}
