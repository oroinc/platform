<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;

use Oro\Bundle\ImportExportBundle\File\FileManager;

use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HttpImportMessageProcessor extends ImportMessageProcessor
{
    /**
     * @var TokenSerializerInterface
     */
    private $tokenSerializer;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param JobRunner $jobRunner
     * @param ImportExportResultSummarizer $importExportResultSummarizer
     * @param JobStorage $jobStorage
     * @param LoggerInterface $logger
     * @param FileManager $fileManager
     * @param HttpImportHandler $importHandler
     * @param TokenSerializerInterface $tokenSerializer
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        JobRunner $jobRunner,
        ImportExportResultSummarizer $importExportResultSummarizer,
        JobStorage $jobStorage,
        LoggerInterface $logger,
        FileManager $fileManager,
        HttpImportHandler $importHandler,
        TokenSerializerInterface $tokenSerializer,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct(
            $jobRunner,
            $importExportResultSummarizer,
            $jobStorage,
            $logger,
            $fileManager,
            $importHandler
        );

        $this->tokenSerializer = $tokenSerializer;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param array $body
     * @param Job $job
     * @return bool
     */
    protected function handleImport(array $body, Job $job)
    {
        if (! $this->setSecurityToken($body['securityToken'])) {
            $this->logger->critical(
                sprintf('[HttpImportMessageProcessor] Cannot set security token'),
                ['message' => $body]
            );
            return null;
        }

        return parent::handleImport($body, $job);
    }

    /**
     * @param MessageInterface $message
     * @return array|null
     */
    protected function getNormalizeBody(MessageInterface $message)
    {
        $body = JSON::decode($message->getBody());

        if (!isset(
            $body['jobId'],
            $body['userId'],
            $body['securityToken'],
            $body['processorAlias'],
            $body['fileName'],
            $body['jobName'],
            $body['process'],
            $body['originFileName']
        )
        ) {
            return null;
        }

        return array_replace_recursive(
            [
                'options' => []
            ],
            $body
        );
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
}
