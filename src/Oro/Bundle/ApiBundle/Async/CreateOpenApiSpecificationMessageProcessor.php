<?php

namespace Oro\Bundle\ApiBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Renderer\OpenApiRenderer;
use Oro\Bundle\ApiBundle\Async\Topic\CreateOpenApiSpecificationTopic;
use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\ApiBundle\Util\OpenApiSpecificationArchive;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\ErrorHandler\BufferingLogger;

/**
 * Creates or renews OpenAPI specification.
 */
class CreateOpenApiSpecificationMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ManagerRegistry $doctrine;
    private OpenApiRenderer $openApiRenderer;
    private OpenApiSpecificationArchive $openApiArchive;
    private LoggerInterface $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        OpenApiRenderer $openApiRenderer,
        OpenApiSpecificationArchive $openApiArchive,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->openApiRenderer = $openApiRenderer;
        $this->openApiArchive = $openApiArchive;
        $this->logger = $logger;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [CreateOpenApiSpecificationTopic::getName()];
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(OpenApiSpecification::class);
        $entity = $em->find(OpenApiSpecification::class, $messageBody['entityId']);
        if (null === $entity) {
            $this->logger->error('The OpenAPI specification was not found.');

            return self::REJECT;
        }
        if (!$this->checkRequirements($entity, $messageBody['renew'])) {
            return self::REJECT;
        }

        $errorLogger = new BufferingLogger();
        $specification = $this->generateOpenApiSpecification($entity, $errorLogger);
        $errorLogs = $errorLogger->cleanLogs();
        if (!$specification || $errorLogs) {
            $this->logger->error('The OpenAPI specification cannot be created.');
            foreach ($errorLogs as [$logLevel, $logMessage, $logContext]) {
                $this->logger->log($logLevel, $logMessage, $logContext);
            }
            $this->markFailed($em, $entity);

            return self::REJECT;
        }

        $specificationCreatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        try {
            $this->markSuccess($em, $entity, $specification, $specificationCreatedAt);
        } catch (\Throwable $e) {
            $errorLogger->error('The OpenAPI specification cannot be created.', ['exception' => $e]);
            $this->markFailed($em, $entity);

            return self::REJECT;
        }

        return self::ACK;
    }

    private function checkRequirements(OpenApiSpecification $entity, bool $renew): bool
    {
        $result = true;
        if ($renew) {
            if ($entity->getStatus() === OpenApiSpecification::STATUS_CREATING) {
                $this->logger->error(
                    'The OpenAPI specification should be already created or a creation was failed before.'
                );
                $result = false;
            }
        } elseif ($entity->getStatus() !== OpenApiSpecification::STATUS_CREATING) {
            $this->logger->error('The OpenAPI specification was already processed.');
            $result = false;
        }

        return $result;
    }

    private function generateOpenApiSpecification(
        OpenApiSpecification $entity,
        BufferingLogger $errorLogger
    ): ?string {
        $options = ['logger' => $errorLogger, 'title' => $entity->getName()];
        $entities = $entity->getEntities();
        if ($entities) {
            $options['entities'] = $entities;
        }
        $serverUrls = $entity->getServerUrls();
        if ($serverUrls) {
            $options['server_urls'] = $serverUrls;
        }
        try {
            return $this->openApiRenderer->render($entity->getView(), $entity->getFormat(), $options);
        } catch (\Throwable $e) {
            $errorLogger->error('The generation of the OpenAPI specification failed.', ['exception' => $e]);

            return null;
        }
    }

    private function markFailed(EntityManagerInterface $em, OpenApiSpecification $entity): void
    {
        $entity->setStatus(OpenApiSpecification::STATUS_FAILED);
        $em->flush();
    }

    private function markSuccess(
        EntityManagerInterface $em,
        OpenApiSpecification $entity,
        string $specification,
        \DateTimeInterface $specificationCreatedAt
    ): void {
        $entity->setSpecification($this->openApiArchive->compress($specification));
        $entity->setSpecificationCreatedAt($specificationCreatedAt);
        $entity->setStatus(OpenApiSpecification::STATUS_CREATED);
        $em->flush();
    }
}
