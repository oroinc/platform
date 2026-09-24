<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Provider;

/**
 * Declares the entity attributes that are not available in email templates but are still resolvable during a render,
 * because the sending code passes their value as an email template parameter.
 *
 * Implemented by the listeners of {@see \Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent}
 * that perform the substitution, so that what is substituted at render time and what the email template security
 * policy check tells the author to use cannot drift apart. Register an implementation with the
 * "oro_email.email_template_substitutable_attribute" tag.
 */
interface SubstitutableEmailTemplateAttributesInterface
{
    /**
     * Returns the email template parameter each substitutable attribute is resolved from, grouped by the entity class
     * that declares the attribute. The attributes of a class apply to its descendants as well.
     *
     * @return array<class-string, array<string, string>> Attribute name to email template parameter name.
     */
    public function getSubstitutableEmailTemplateAttributes(): array;
}
