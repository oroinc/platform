<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\EmailTemplateCandidates;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;

/**
 * Composite email template candidates provider that collects template names from inner providers.
 */
class EmailTemplateCandidatesProvider implements EmailTemplateCandidatesProviderInterface
{
    /**
     * @var iterable<EmailTemplateCandidatesProviderInterface>
     */
    private iterable $emailTemplateCandidatesProviders;

    /**
     * @param iterable<EmailTemplateCandidatesProviderInterface> $emailTemplateCandidatesProviders
     */
    public function __construct(iterable $emailTemplateCandidatesProviders)
    {
        $this->emailTemplateCandidatesProviders = $emailTemplateCandidatesProviders;
    }

    #[\Override]
    public function getCandidatesNames(EmailTemplateCriteria $emailTemplateCriteria, array $templateContext = []): array
    {
        $emailTemplateNames = [];
        foreach ($this->emailTemplateCandidatesProviders as $emailTemplateCandidatesProvider) {
            $emailTemplateNames[] = $emailTemplateCandidatesProvider
                ->getCandidatesNames($emailTemplateCriteria, $templateContext);
        }

        $emailTemplateNames[] = [$emailTemplateCriteria->getName()];

        return array_merge(...$emailTemplateNames);
    }
}
