<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class LoadWorkflowEmailTemplates extends AbstractFixture implements DependentFixtureInterface
{
    public const WFA_EMAIL_TEMPLATE_NAME = 'wfa_email_template';

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $template = '%s="{{ entity.url.%s }}";';
        $routes = ['index', 'view', 'create', 'update', 'delete'];
        $content = '';
        foreach ($routes as $route) {
            $content .= sprintf($template, $route, $route);
        }

        $entity = new EmailTemplate();
        $entity->setName(self::WFA_EMAIL_TEMPLATE_NAME);
        $entity->setEntityName(WorkflowAwareEntity::class);
        $entity->setOwner($this->getReference(LoadUser::USER));
        $entity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
        $entity->setIsEditable(true);
        $entity->setIsSystem(true);
        $entity->setContent($content);
        $entity->setSubject('{{ workflowName }} {{ transitionName }}');
        $manager->persist($entity);
        $this->addReference(self::WFA_EMAIL_TEMPLATE_NAME, $entity);
        $manager->flush();
    }
}
