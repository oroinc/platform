<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\UserBundle\Entity\User;

class LoadEmailTemplateWithUserEntity extends AbstractFixture
{
    public const string TEMPLATE_REFERENCE = 'email_template_with_user_entity';

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
