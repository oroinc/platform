<?php

namespace Oro\Bundle\ImportExportBundle\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Manager\ImportExportResultManager;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job as JobEntity;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Responsible for processing the results of import or export before they are stored
 */
class SaveImportExportResultProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ImportExportResultManager
     */
    protected $importExportResultManager;

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ImportExportResultManager $importExportResultManager,
        UserManager $userManager,
        DoctrineHelper $doctrineHelper,
        LoggerInterface $logger
    ) {
        $this->importExportResultManager = $importExportResultManager;
        $this->userManager = $userManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        try {
            $options = $this->configureOption($body);
        } catch (MissingOptionsException | UndefinedOptionsException | InvalidOptionsException $e) {
            $this->logger->critical(
                sprintf('Error occurred during save result: %s', $e->getMessage()),
                ['exception' => $e]
            );
            return self::REJECT;
        }

        $job = $this->getJobRepository()->findJobById((int)$options['jobId']);
        $job = $job->isRoot() ? $job : $job->getRootJob();

        try {
            $this->saveJobResult($job, $options);
        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf('Unhandled error save result: %s', $e->getMessage()),
                ['exception' => $e]
            );
            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    private function configureOption($parameters = []): array
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setRequired('jobId');
        $optionResolver->setRequired('entity')->setAllowedTypes('entity', 'string');
        $optionResolver->setRequired('type')->setAllowedValues('type', [
            ProcessorRegistry::TYPE_EXPORT,
            ProcessorRegistry::TYPE_IMPORT,
            ProcessorRegistry::TYPE_IMPORT_VALIDATION,
        ]);
        $optionResolver->setDefined('userId')->setDefault('userId', null);

        $optionResolver->setDefined('owner')->setDefault('owner', function (Options $options) {
            return $this->findOwnerById($options['userId']);
        });

        $optionResolver->setDefined('options')
            ->setAllowedTypes('options', ['array'])
            ->setDefault('options', []);

        return $optionResolver->resolve($parameters);
    }

    /**
     * @param $ownerId
     *
     * @return User
     */
    private function findOwnerById($ownerId = null): ?User
    {
        return $ownerId ? $this->userManager->findUserBy(['id' => $ownerId]) : null;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function saveJobResult(Job $job, array $parameters): void
    {
        $jobData = $job->getData();
        $this->importExportResultManager->saveResult(
            $job->getId(),
            $parameters['type'],
            $parameters['entity'],
            $parameters['owner'],
            $jobData['file'] ?? null,
            $parameters['options']
        );
    }

    public static function getSubscribedTopics(): array
    {
        return [Topics::SAVE_IMPORT_EXPORT_RESULT];
    }

    private function getJobRepository(): JobRepository
    {
        return $this->doctrineHelper->getEntityRepository(JobEntity::class);
    }
}
