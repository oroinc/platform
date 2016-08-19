<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Reader;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationReader extends AbstractReader
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var Translator */
    protected $translator;

    /** @var Language[] */
    protected $languages;

    /** @var array */
    protected $messages;

    /**
     * @param ContextRegistry $contextRegistry
     * @param ManagerRegistry $registry
     * @param Translator $translator
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        ManagerRegistry $registry,
        Translator $translator
    ) {
        parent::__construct($contextRegistry);

        $this->registry = $registry;
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
            $language = $this->getLanguage($languageId);
            $locale = $language->getCode();

            $defaultMessages = $this->getMessages(Translation::DEFAULT_LOCALE);
            $originalMessages = $this->getMessages($locale, true);

            error_log('defaultMessages: ' . json_encode([count($defaultMessages), count($originalMessages)]));

            $defaults = ['locale' => $locale, 'original_value' => ''];

            $messages = [];
            foreach ($defaultMessages as $key => $value) {
                $messages[$key] = array_merge(
                    $value,
                    isset($originalMessages[$key]) ? $originalMessages[$key] : $defaults
                );
            }

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
                    'original_value' => $value,
                ];

                $messages[sprintf('%s.%s', $domain, $key)] = $message;
            }
        }

        ksort($messages, SORT_STRING | SORT_FLAG_CASE);

        return $messages;
    }

    /**
     * @param int $id
     * @return Language
     */
    protected function getLanguage($id)
    {
        if (!isset($this->languages[$id])) {
            $languages[$id] = $this->registry->getManagerForClass(Language::class)
                ->getRepository(Language::class)
                ->find($id);
        }

        return $languages[$id];
    }
}
