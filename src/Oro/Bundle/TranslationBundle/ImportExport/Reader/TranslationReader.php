<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Reader;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationReader extends AbstractReader
{
    /** @var array */
    protected $messages;

    /** @var LanguageRepository */
    protected $languageRepository;

    /**
     * @param ContextRegistry $contextRegistry
     * @param LanguageRepository $languageRepository
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        LanguageRepository $languageRepository
    ) {
        parent::__construct($contextRegistry);

        $this->languageRepository = $languageRepository;
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
     *
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

            array_walk($messages, function (array &$message, $key) use ($locale, $defaultMessages) {
                $message = array_merge(
                    $message,
                    [
                        'english_translation' => isset($defaultMessages[$key]) ? $defaultMessages[$key]['value'] : '',
                    ]
                );
            });

            $this->messages[$locale] = array_values($messages);
        }

        return $this->messages[$locale];
    }

    /**
     * @param string $locale
     *
     * @return array
     */
    protected function getMessages($locale)
    {
        $messages = $this->languageRepository->getTranslationsForExport($locale);

        foreach ($messages as $index => $message) {
            $messages[sprintf('%s.%s', $message['domain'], $message['key'])] = $message;
            unset($messages[$index]);
        }

        return $messages;
    }
}
