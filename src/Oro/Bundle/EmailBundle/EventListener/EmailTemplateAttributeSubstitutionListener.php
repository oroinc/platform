<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent;
use Oro\Bundle\EmailBundle\Provider\SubstitutableEmailTemplateAttributesInterface;

/**
 * Resolves one entity attribute that the email templates rendering sandbox denies from an email template parameter
 * the sending code passed, so that a template reading the attribute keeps rendering while the attribute itself stays
 * out of the email template variables.
 */
final class EmailTemplateAttributeSubstitutionListener implements SubstitutableEmailTemplateAttributesInterface
{
    /**
     * @param class-string $entityClass The entity that declares the attribute; descendants are covered as well.
     * @param string $attributeName The attribute the rendering sandbox denies.
     * @param string $templateParameterName The email template parameter the value is taken from.
     */
    public function __construct(
        private readonly string $entityClass,
        private readonly string $attributeName,
        private readonly string $templateParameterName
    ) {
    }

    #[\Override]
    public function getSubstitutableEmailTemplateAttributes(): array
    {
        return [$this->entityClass => [$this->attributeName => $this->templateParameterName]];
    }

    public function onSecurityPolicyViolation(EmailTemplateSecurityPolicyViolationEvent $event): void
    {
        if ($event->isHandled() || $event->getItem() !== $this->attributeName) {
            return;
        }

        $object = $event->getObject();
        if (!$object instanceof $this->entityClass) {
            return;
        }

        $context = $event->getContext();
        if (!\array_key_exists($this->templateParameterName, $context)) {
            return;
        }

        $event->setValue($context[$this->templateParameterName]);
    }
}
