<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Loads an email template whose content accesses User entity fields,
 * used by the field-level post-upgrade task tests.
 */
class LoadEmailTemplateForFieldAvailableInTemplateTask extends AbstractFixture
{
    public const TEMPLATE_REFERENCE = 'email_template_for_field_available_in_template_task';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $template = new EmailTemplate(self::TEMPLATE_REFERENCE);
        $template
            ->setEntityName(User::class)
            ->setSubject('Hello {{ entity.firstName }}')
            ->setContent('Dear {{ entity.firstName }} {{ entity.lastName }}');

        $manager->persist($template);
        $manager->flush();

        $this->setReference(self::TEMPLATE_REFERENCE, $template);
    }
}
