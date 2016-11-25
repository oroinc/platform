<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Reader;

use Symfony\Component\Translation\TranslatorBagInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationReader extends AbstractReader
{
    /** @var TranslatorBagInterface */
    protected $translator;

    /** @var array */
    protected $messages;

    /**
     * @param ContextRegistry $contextRegistry
     * @param TranslatorBagInterface $translator
     */
    public function __construct(ContextRegistry $contextRegistry, TranslatorBagInterface $translator)
    {
        parent::__construct($contextRegistry);

        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $offset = $this->getStepExecution()->getReadCount();
        $messages = $this->getLanguageMessages($this->getContext()->getOption('language_code'));

        if (!isset($messages[$offset])) {
            return null;
        }

        $this->getStepExecution()->incrementReadCount();

        return $messages[$offset];
    }

    /**
     * @param string $locale
     * @return array
     */
    protected function getLanguageMessages($locale)
    {
        if (!$locale) {
            return [];
        }

        if (!isset($this->messages[$locale])) {
            $defaultMessages = $this->getMessages(Translator::DEFAULT_LOCALE);
            $originalMessages = $this->getMessages($locale);

            $messages = array_merge($defaultMessages, $originalMessages);

            ksort($messages, SORT_STRING | SORT_FLAG_CASE);

            array_walk($messages, function (array &$message, $key) use ($locale, $originalMessages, $defaultMessages) {
                $message = array_merge(
                    $message,
                    [
                        'original_value' => isset($originalMessages[$key]) ? $originalMessages[$key]['value'] : '',
                    ]
                );
            });

            $this->messages[$locale] = array_values($messages);
        }

        return $this->messages[$locale];
    }

    /**
     * @param string $locale
     * @return array
     */
    protected function getMessages($locale)
    {
        $catalogue = $this->translator->getCatalogue($locale);

        $messages = [];

        foreach ($catalogue->getDomains() as $domain) {
            foreach ($catalogue->all($domain) as $key => $value) {
                $message = [
                    'domain' => $domain,
                    'key' => $key,
                    'value' => $value,
                ];

                $messages[sprintf('%s.%s', $domain, $key)] = $message;
            }
        }

        return $messages;
    }
}
