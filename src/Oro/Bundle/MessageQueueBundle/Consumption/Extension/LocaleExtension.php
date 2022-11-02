<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * The message consumption extension that sets locale settings from system configuration
 * to Locale and TranslatableListener.
 */
class LocaleExtension extends AbstractExtension
{
    /** @var LocaleSettings */
    private $localeSettings;

    /** @var TranslatableListener */
    private $translatableListener;

    public function __construct(LocaleSettings $localeSettings, TranslatableListener $translatableListener)
    {
        $this->localeSettings = $localeSettings;
        $this->translatableListener = $translatableListener;
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context): void
    {
        \Locale::setDefault($this->localeSettings->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context): void
    {
        $this->translatableListener->setTranslatableLocale($this->localeSettings->getLanguage());
    }
}
