<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\Twig\SecurityPolicy;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

/**
 * Creates EmailTemplate entity records used by EmailTemplateSecurityPolicyCheckerTest.
 *
 * The model class Oro\Bundle\EmailBundle\Model\EmailTemplate is stored as entityName
 * because the TestEntityVariablesProvider (services_test.yml) registers exactly that class
 * with a predictable set of allowed properties (['subject']) and methods (['getSubject']).
 * This makes property and method violation assertions fully deterministic.
 */
class LoadEmailTemplateSecurityPolicyCheckerData extends AbstractFixture implements DependentFixtureInterface
{
    public const TEMPLATE_WITH_MODEL_ENTITY_CLASS_REFERENCE = 'security_policy_template_with_model_entity_class';

    /** Name stored in the oro_email_template table used to trigger entityName resolution */
    public const TEMPLATE_WITH_MODEL_ENTITY_CLASS_NAME = 'security_policy_email_template_model_entity';

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        $templateWithModelEntity = new EmailTemplate(self::TEMPLATE_WITH_MODEL_ENTITY_CLASS_NAME, '');
        $templateWithModelEntity->setEntityName(EmailTemplateModel::class);
        $templateWithModelEntity->setOrganization($organization);
        $manager->persist($templateWithModelEntity);

        $manager->flush();

        $this->setReference(
            self::TEMPLATE_WITH_MODEL_ENTITY_CLASS_REFERENCE,
            $templateWithModelEntity,
        );
    }
}
