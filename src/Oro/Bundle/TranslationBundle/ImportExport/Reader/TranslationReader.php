<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Reader;

use Symfony\Component\Translation\DataCollectorTranslator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;

class TranslationReader extends AbstractReader
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var Translator */
    protected $translator;

    /** @var Language[] */
    protected $languages;

    /** @var array */
    protected $messages;

    /**
     * @param ContextRegistry $contextRegistry
     * @param DoctrineHelper $doctrineHelper
     * @param DataCollectorTranslator $translator
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        DoctrineHelper $doctrineHelper,
        DataCollectorTranslator $translator
    ) {
        parent::__construct($contextRegistry);

        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $offset = $this->getStepExecution()->getReadCount();

        $messages = $this->getLanguageMessages($this->getContext()->getOption('language_id'));

        if (!isset($messages[$offset])) {
            return;
        }

        $this->getStepExecution()->incrementReadCount();

        return $messages[$offset];
    }

    /**
     * @param int $languageId
     * @return array
     */
    protected function getLanguageMessages($languageId)
    {
        if (!isset($this->messages[$languageId])) {
            $locale = $this->getLanguage($languageId)->getCode();

            $defaultMessages = $this->getMessages(Translation::DEFAULT_LOCALE);
            $originalMessages = $this->getMessages($locale, true);

            $messages = array_merge($defaultMessages, $originalMessages);

            ksort($messages, SORT_STRING | SORT_FLAG_CASE);

            array_walk($messages, function(array &$message, $key) use ($locale, $originalMessages, $defaultMessages) {
                $message = array_merge(
                    $message,
                    [
                        'locale' => $locale,
                        'original_value' => isset($originalMessages[$key]) ? $originalMessages[$key]['value'] : '',
                        'default_value' => isset($defaultMessages[$key]) ? $defaultMessages[$key]['value'] : '',
                    ]
                );
            });

            $this->messages[$languageId] = array_values($messages);
        }

        return $this->messages[$languageId];
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
                    'locale' => $locale,
                    'domain' => $domain,
                    'key' => $key,
                    'value' => $value,
                ];

                $messages[sprintf('%s.%s', $domain, $key)] = $message;
            }
        }

        return $messages;
    }

    /**
     * @param int $id
     * @return Language
     */
    protected function getLanguage($id)
    {
        return $this->doctrineHelper->getEntityReference(Language::class, $id);
    }
}
