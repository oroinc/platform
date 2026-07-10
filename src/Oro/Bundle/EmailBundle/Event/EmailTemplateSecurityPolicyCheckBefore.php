<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before the Twig sandbox security policy is checked on an email template.
 *
 * This event allows listeners to modify the variable type mapping used for security checks.
 * It is useful for providing additional type information about variables available in the template context,
 * beyond the root entity, to improve the reliability and comprehensiveness of the security policy check.
 */
class EmailTemplateSecurityPolicyCheckBefore extends Event
{
    /**
     * @param EmailTemplateInterface $emailTemplate The email template being checked.
     * @param array<string, string> $variableTypes Mapping of variable name to entity class (variable name => FQCN).
     */
    public function __construct(
        private readonly EmailTemplateInterface $emailTemplate,
        private array $variableTypes
    ) {
    }

    public function getEmailTemplate(): EmailTemplateInterface
    {
        return $this->emailTemplate;
    }

    /**
     * Gets the mapping of variable names to entity classes.
     *
     * @return array<string, string> Variable name => entity FQCN
     */
    public function getVariableTypes(): array
    {
        return $this->variableTypes;
    }

    /**
     * Sets the mapping of variable names to entity classes.
     *
     * @param array<string, string> $variableTypes Variable name => entity FQCN
     */
    public function setVariableTypes(array $variableTypes): void
    {
        $this->variableTypes = $variableTypes;
    }
}
