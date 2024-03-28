<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\EmailTemplateCandidates;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;

/**
 * Interface for an email template candidates names providers.
 * The main goal of collecting multiple email template names is to provide the ability to use different
 * email templates loaders depending on an email template name and context.
 */
interface EmailTemplateCandidatesProviderInterface
{
    /**
     * @param EmailTemplateCriteria $emailTemplateCriteria
     * @param array $templateContext Email template context. Example:
     *  [
     *      'localization' => Localization|int $localization,
     *      // ... other context parameters supported by the existing candidates names
     *      // providers {@see EmailTemplateCandidatesProviderInterface}
     *  ]
     *
     * @return string[] An array of email template names that could be passed to an
     *  email template loader {@see \Oro\Bundle\EmailBundle\Twig\EmailTemplateLoader\EmailTemplateLoaderInterface}
     */
    public function getCandidatesNames(
        EmailTemplateCriteria $emailTemplateCriteria,
        array $templateContext = []
    ): array;
}
