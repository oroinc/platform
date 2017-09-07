<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\CliImportHandler;
use Oro\Bundle\ImportExportBundle\Handler\PostponedRowsHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;

class CliImportMessageProcessor extends ImportMessageProcessor
{
    /** @var TokenSerializerInterface */
    private $tokenSerializer;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /**
     * @param JobRunner                    $jobRunner
     * @param ImportExportResultSummarizer $importExportResultSummarizer
     * @param JobStorage                   $jobStorage
     * @param LoggerInterface              $logger
     * @param FileManager                  $fileManager
     * @param CliImportHandler            $importHandler
     * @param PostponedRowsHandler         $postponedRowsHandler
     * @param TokenSerializerInterface     $tokenSerializer
     * @param TokenStorageInterface        $tokenStorage
     */
    public function __construct(
        JobRunner $jobRunner,
        ImportExportResultSummarizer $importExportResultSummarizer,
        JobStorage $jobStorage,
        LoggerInterface $logger,
        FileManager $fileManager,
        CliImportHandler $importHandler,
        PostponedRowsHandler $postponedRowsHandler,
        TokenSerializerInterface $tokenSerializer,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct(
            $jobRunner,
            $importExportResultSummarizer,
            $jobStorage,
            $logger,
            $fileManager,
            $importHandler,
            $postponedRowsHandler
        );

        $this->tokenSerializer = $tokenSerializer;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleImport(array $body, Job $job, JobRunner $jobRunner)
    {
        $this->setSecurityToken($body);

        return parent::handleImport($body, $job, $jobRunner);
    }

    /**
     * @param array $body
     */
    private function setSecurityToken($body)
    {
        if (!isset($body['securityToken'])) {
            return;
        }
        $serializedToken = $body['securityToken'];
        $token = $this->tokenSerializer->deserialize($serializedToken);

        if (null === $token) {
            return;
        }

        $this->tokenStorage->setToken($token);
    }
}
