<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EmailBundle\Event\EmailTemplateRenderAfterEvent;
use Oro\Bundle\EmailBundle\Event\EmailTemplateRenderBeforeEvent;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;

/**
 * Sets the localization specified in template context as current localization before an email template is rendered.
 * Reverts to the original localization after an email template is rendered.
 */
class EmailTemplateRenderLocalizationAwareEventListener
{
    private LocalizationProviderInterface $currentLocalizationProvider;

    private Localization|bool|null $originalLocalization = false;

    public function __construct(LocalizationProviderInterface $currentLocalizationProvider)
    {
        $this->currentLocalizationProvider = $currentLocalizationProvider;
    }

    public function onRenderBefore(EmailTemplateRenderBeforeEvent $event): void
    {
        $localization = $event->getTemplateContextParameter('localization');
        if (!$localization instanceof Localization) {
            return;
        }

        $this->originalLocalization = $this->currentLocalizationProvider->getCurrentLocalization();
        $this->currentLocalizationProvider->setCurrentLocalization($localization);
    }

    public function onRenderAfter(EmailTemplateRenderAfterEvent $event): void
    {
        if ($this->originalLocalization !== false) {
            $this->currentLocalizationProvider->setCurrentLocalization($this->originalLocalization);
            $this->originalLocalization = false;
        }
    }
}
