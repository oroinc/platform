<?php

namespace Oro\Bundle\TranslationBundle\Async;

use Oro\Bundle\TranslationBundle\Async\Topic\DumpJsTranslationsTopic;
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
    private JsTranslationDumper $dumper;
    private LoggerInterface $logger;

    public function __construct(JsTranslationDumper $dumper, LoggerInterface $logger)
    {
        $this->dumper = $dumper;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $locales = $this->dumper->getAllLocales();
        try {
            foreach ($locales as $locale) {
                $this->logger->info('Update JS translations for {locale}.', ['locale' => $locale]);
                $translationFile = $this->dumper->dumpTranslationFile($locale);
                $this->logger->info(
                    'The JS translations for {locale} have been dumped into {file}.',
                    ['locale' => $locale, 'file' => $translationFile]
                );
            }
        } catch (IOException $e) {
            $this->logger->error('Cannot update JS translations.', ['exception' => $e]);

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [DumpJsTranslationsTopic::getName()];
    }
}
