<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationHelper
{
    const WORKFLOWS_DOMAIN = 'workflows';

    /** @var Translator */
    private $translator;

    /** @var TranslationManager */
    private $translationManager;

    /** @var string */
    private $currentLocale;

    /**
     * @param Translator $translator
     * @param TranslationManager $translationManager
     */
    public function __construct(
        Translator $translator,
        TranslationManager $translationManager
    ) {
        $this->translator = $translator;
        $this->translationManager = $translationManager;
    }

    /**
     * @param string $key
     * @param string $value
     *
     */
    public function saveTranslation($key, $value)
    {
        if (!$this->currentLocale) {
            $this->currentLocale = $this->translator->getLocale();
        }
        $this->translationManager
            ->saveValue($key, $value, $this->currentLocale, self::WORKFLOWS_DOMAIN);
    }

    /**
     * @param string $key
     */
    public function ensureTranslationKey($key)
    {
        $this->translationManager->findTranslationKey($key, self::WORKFLOWS_DOMAIN);
    }

    /**
     * @param string $key
     */
    public function removeTranslationKey($key)
    {
        $this->translationManager->removeTranslationKey($key, self::WORKFLOWS_DOMAIN);
    }
}
