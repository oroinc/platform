<?php

namespace Oro\Bundle\ImportExportBundle\Async;

use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\PostHttpImportEvent;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Responsible for processing the results of import or export before they are stored
 */
class SaveImportExportResultProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, LoggerInterface $logger)
    {
        $this->eventDispatcher = $eventDispatcher;
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

            $this->eventDispatcher->dispatch(Events::POST_HTTP_IMPORT, new PostHttpImportEvent($options['options']));
        } catch (MissingOptionsException $e) {
            $this->logger->critical(
                sprintf('Error occurred during save result: %s', $e->getMessage()),
                ['exception' => $e]
            );
            return self::REJECT;
        } catch (UndefinedOptionsException $e) {
            $this->logger->critical(
                sprintf('Error occurred during save result: %s', $e->getMessage()),
                ['exception' => $e]
            );
            return self::REJECT;
        } catch (InvalidOptionsException $e) {
            $this->logger->critical(
                sprintf('Not enough required parameters: %s', $e->getMessage()),
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
    private function configureOption(array $parameters = []): array
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
        $optionResolver->setDefined('notifyEmail')->setDefault('notifyEmail', null);
        $optionResolver->setDefined('options')
            ->setAllowedTypes('options', ['array'])
            ->setDefault('options', []);

        return $optionResolver->resolve($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [Topics::SAVE_IMPORT_EXPORT_RESULT];
    }
}
