<?php

namespace Oro\Bundle\TranslationBundle\Async;

use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Dumps JS translations.
 */
class DumpJsTranslationsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var JsTranslationDumper */
    private $dumper;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(JsTranslationDumper $dumper, LoggerInterface $logger)
    {
        $this->dumper = $dumper;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $this->dumper->setLogger($this->logger);
        try {
            $this->dumper->dumpTranslations();
        } catch (IOException $e) {
            $this->logger->error(
                'Cannot update JS translations.',
                ['exception' => $e]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::JS_TRANSLATIONS_DUMP];
    }
}
