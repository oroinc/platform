<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;

/**
 * This event is dispatched before creating an email template from an array.
 * It allows modifying the email template and its data before it is created.
 */
class EmailTemplateFromArrayHydrateBeforeEvent
{
    /**
     * @param EmailTemplateInterface $emailTemplate The email template to be hydrated.
     * @param array $data The data to be used for hydrating the email template where keys are the properties
     *  of the email template.
     */
    public function __construct(
        private readonly EmailTemplateInterface $emailTemplate,
        private array $data
    ) {
    }

    public function getEmailTemplate(): EmailTemplateInterface
    {
        return $this->emailTemplate;
    }

    /**
     * Returns the data to be used for hydrating the email template.
     * The data structure should consist of key-value pairs, where keys are the properties of the email template.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Sets the data to be used for hydrating the email template.
     * The data structure should consist of key-value pairs, where keys are the properties of the email template.
     *
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
