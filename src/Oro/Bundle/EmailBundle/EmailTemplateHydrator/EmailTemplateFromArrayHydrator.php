<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\EmailTemplateHydrator;

use Oro\Bundle\EmailBundle\Event\EmailTemplateFromArrayHydrateBeforeEvent;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Hydrates an email template from array.
 *
 * The array structure should consist of key-value pairs, where keys are the properties of the email template:
 *  [
 *      'name' => 'email_template_name',
 *      'subject' => 'Subject of the email',
 *      'type' => 'html',
 *      'content' => 'Email template content goes here.',
 *      // ...
 *  ]
 */
class EmailTemplateFromArrayHydrator
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function hydrateFromArray(EmailTemplate $emailTemplate, array $data): void
    {
        $event = $this->eventDispatcher->dispatch(
            new EmailTemplateFromArrayHydrateBeforeEvent($emailTemplate, $data)
        );

        $emailTemplateData = $event->getData();

        foreach ($emailTemplateData as $key => $val) {
            if ($this->propertyAccessor->isWritable($emailTemplate, $key)) {
                $this->propertyAccessor->setValue($emailTemplate, $key, $val);
            }
        }
    }
}
