<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EmailBundle\Event\EmailTemplateContextCollectEvent;
use Oro\Bundle\LocaleBundle\Provider\PreferredLocalizationProviderInterface;

/**
 * Sets a preferred recipient localization to an email template criteria context.
 */
class EmailTemplateContextCollectLocalizationAwareEventListener
{
    private PreferredLocalizationProviderInterface $preferredLocalizationProvider;

    public function __construct(PreferredLocalizationProviderInterface $preferredLocalizationProvider)
    {
        $this->preferredLocalizationProvider = $preferredLocalizationProvider;
    }

    public function onContextCollect(EmailTemplateContextCollectEvent $event): void
    {
        if ($event->getTemplateContextParameter('localization') !== null) {
            return;
        }

        $recipients = $event->getRecipients();
        if (!$recipients) {
            return;
        }

        $localization = $this->preferredLocalizationProvider->getPreferredLocalization(reset($recipients));
        if ($localization !== null) {
            $event->setTemplateContextParameter('localization', $localization);
        }
    }
}
