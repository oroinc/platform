<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Event\EmailTemplateContextCollectEvent;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Collects and provides an email template context required for email template loading and rendering.
 */
class EmailTemplateContextProvider
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param From $from
     * @param EmailHolderInterface|array $recipients
     * @param EmailTemplateCriteria|string $templateName
     * @param array $templateParams
     * @return array An email template context required for email template loading and rendering
     *  [
     *      'localization' => Localization $localization,
     *      // ... other context parameters supplied by event listeners
     *  ]
     */
    public function getTemplateContext(
        From $from,
        EmailHolderInterface|array $recipients,
        EmailTemplateCriteria|string $templateName,
        array $templateParams = []
    ): array {
        $recipients = !is_array($recipients) ? [$recipients] : $recipients;
        $emailTemplateCriteria = is_scalar($templateName) ? new EmailTemplateCriteria($templateName) : $templateName;

        $event = new EmailTemplateContextCollectEvent($from, $recipients, $emailTemplateCriteria, $templateParams);
        $this->eventDispatcher->dispatch($event);

        return $event->getTemplateContext();
    }
}
