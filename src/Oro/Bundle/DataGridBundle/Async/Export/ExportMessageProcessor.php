<?php
namespace Oro\Bundle\DataGridBundle\Async\Export;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportConnector;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

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
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var TokenSerializerInterface
     */
    private $tokenSerializer;

    /**
     * @param ExportHandler $exportHandler
     * @param JobRunner $jobRunner
     * @param DatagridExportConnector $exportConnector
     * @param ExportProcessor $exportProcessor
     * @param WriterChain $writerChain
     * @param TokenStorageInterface $tokenStorage
     * @param JobStorage $jobStorage
     * @param LoggerInterface $logger
     */
    public function __construct(
        ExportHandler $exportHandler,
        JobRunner $jobRunner,
        DatagridExportConnector $exportConnector,
        ExportProcessor $exportProcessor,
        WriterChain $writerChain,
        TokenStorageInterface $tokenStorage,
        JobStorage $jobStorage,
        LoggerInterface $logger
    ) {
        $this->exportHandler = $exportHandler;
        $this->jobRunner = $jobRunner;
        $this->exportConnector = $exportConnector;
        $this->exportProcessor = $exportProcessor;
        $this->writerChain = $writerChain;
        $this->tokenStorage = $tokenStorage;
        $this->jobStorage = $jobStorage;
        $this->logger = $logger;
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
            'jobId' => null,
            'format' => null,
            'batchSize' => 200,
            'parameters' => [
                'gridName' => null,
                'gridParameters' => [],
                FormatterProvider::FORMAT_TYPE => 'excel',
            ],
            'securityToken' => null,
        ], $body);

        if (! isset($body['jobId'], $body['securityToken'], $body['parameters']['gridName'], $body['format'])) {
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

        if (! $this->setSecurityToken($body['securityToken'])) {
            $this->logger->critical(
                sprintf('[DataGridExportMessageProcessor] Cannot set security token'),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $contextParameters = new ParameterBag($body['parameters']['gridParameters']);
        $contextParameters->set(ActionExtension::ENABLE_ACTIONS_PARAMETER, false);
        $body['parameters']['gridParameters'] = $contextParameters;

        $jobUniqueName = sprintf(
            'datagrid_export_%s_%s_%s',
            $body['parameters']['gridName'],
            $body['format'],
            $this->getUser()->getId()
        );

        $result = $this->jobRunner->runDelayed(
            $body['jobId'],
            function (JobRunner $jobRunner, Job $job) use ($body, $jobUniqueName, $writer) {
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
                    $exportResult['success'] ? 'Yes' : 'No'
                ));

                $this->saveJobResult($job, $exportResult);

                return $exportResult['success'];
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @return array
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXPORT];
    }

    /**
     * @param string $serializedToken
     *
     * @return bool
     */
    private function setSecurityToken($serializedToken)
    {
        $token = $this->tokenSerializer->deserialize($serializedToken);

        if (null === $token) {
            return false;
        }

        $this->tokenStorage->setToken($token);

        return true;
    }

    /**
     * @return UserInterface
     *
     * @throws \RuntimeException
     */
    private function getUser()
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \RuntimeException('Security token is null');
        }

        $user = $token->getUser();

        if (! is_object($user) || ! method_exists($user, 'getId') || ! method_exists($user, 'getEmail')) {
            throw new \RuntimeException('Not supported user type');
        }

        return $user;
    }

    /**
     * @param Job $job
     * @param array $data
     */
    private function saveJobResult(Job $job, array $data)
    {
        $this->jobStorage->saveJob($job, function (Job $job) use ($data) {
            $job->setData($data);
        });
    }
}
