<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;

/**
 * Loads two email templates for the security policy check command functional tests:
 * - A clean template with no Twig sandbox violations.
 * - A template that uses the "json_encode" filter, which is not in the allowed filter list,
 *   and therefore triggers a security policy violation.
 */
class LoadEmailTemplatesForSecurityPolicyCheckData extends AbstractFixture
{
    public const string CLEAN_TEMPLATE = 'security_check_clean_template';
    public const string VIOLATION_TEMPLATE = 'security_check_violation_template';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $cleanTemplate = new EmailTemplate(self::CLEAN_TEMPLATE);
        $cleanTemplate
            ->setSubject('Hello {{ recipient_name }}')
            ->setContent('Simple content without Twig sandbox violations.');

        // Uses "json_encode" filter, which is NOT in the allowed filters list
        // (see oro_email.twig.email_security_policy in services.yml).
        // This causes a SecurityNotAllowedFilterError during static analysis.
        $violationTemplate = new EmailTemplate(self::VIOLATION_TEMPLATE);
        $violationTemplate
            ->setSubject('Subject')
            ->setContent("{{ 'data'|json_encode }}");

        $manager->persist($cleanTemplate);
        $manager->persist($violationTemplate);
        $manager->flush();

        $this->setReference(self::CLEAN_TEMPLATE, $cleanTemplate);
        $this->setReference(self::VIOLATION_TEMPLATE, $violationTemplate);
    }
}
