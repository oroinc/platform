<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Reader;

use Symfony\Component\Translation\DataCollectorTranslator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;

use Oro\Bundle\TranslationBundle\Entity\Translation;

class TranslationReader extends AbstractReader
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var DataCollectorTranslator */
    protected $translator;

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

        $messages = $this->getLanguageMessages($this->getContext()->getOption('language_code'));

        if (!isset($messages[$offset])) {
            return null;
        }

        $this->getStepExecution()->incrementReadCount();

        return $messages[$offset];
    }

    /**
     * @param int $locale
     * @return array
     */
    protected function getLanguageMessages($locale)
    {
        if (!isset($this->messages[$locale])) {

            $defaultMessages = $this->getMessages(Translation::DEFAULT_LOCALE);
            $originalMessages = $this->getMessages($locale, true);

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
