<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Loads an email template bound to the TestActivity entity together with a TestActivity record owned by
 * the system administrator, so that authorization on the compiled target record can be tested without
 * downgrading permissions on the User entity the test principal itself belongs to.
 */
class LoadEmailTemplateWithTestActivityData extends AbstractFixture implements DependentFixtureInterface
{
    public const string TEST_ACTIVITY = 'email_template_test_activity_target';
    public const string TEST_ACTIVITY_TEMPLATE = 'email_template_for_test_activity';

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUser::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $owner */
        $owner = $this->getReference(LoadUser::USER);
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        $testActivity = new TestActivity();
        $testActivity->setMessage('Test activity message');
        $testActivity->setDescription('Test activity description');
        $testActivity->setOwner($owner);
        $testActivity->setOrganization($organization);

        $emailTemplate = new EmailTemplate(
            'test_activity_template',
            'Test activity content {{ entity.message }}'
        );
        $emailTemplate->setSubject('Test activity subject {{ entity.message }}');
        $emailTemplate->setEntityName(TestActivity::class);
        $emailTemplate->setOrganization($organization);
        $emailTemplate->setType('html');

        $manager->persist($testActivity);
        $manager->persist($emailTemplate);
        $manager->flush();

        $this->setReference(self::TEST_ACTIVITY, $testActivity);
        $this->setReference(self::TEST_ACTIVITY_TEMPLATE, $emailTemplate);
    }
}
