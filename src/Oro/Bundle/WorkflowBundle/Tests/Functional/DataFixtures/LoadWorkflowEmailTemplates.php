<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\UserBundle\Entity\User;

class LoadWorkflowEmailTemplates extends AbstractFixture
{
    const WFA_EMAIL_TEMPLATE_NAME = 'wfa_email_template';

    /** @var User */
    protected $adminUser;

    /** @var Organization */
    protected $organization;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $template = '%s="{{ entity.url.%s }}";';
        $routes = ['index', 'view', 'create', 'update', 'delete',];
        $content = '';
        foreach ($routes as $route) {
            $content .= sprintf($template, $route, $route);
        }

        $entity = new EmailTemplate();
        $entity->setName(self::WFA_EMAIL_TEMPLATE_NAME)
            ->setEntityName(WorkflowAwareEntity::class)
            ->setOwner($this->getAdminUser($manager))
            ->setOrganization($this->getOrganization($manager))
            ->setIsEditable(true)
            ->setIsSystem(true);
        $entity->setContent($content);
        $entity->setSubject('{{ workflowName }} {{ transitionName }}');

        $manager->persist($entity);
        $manager->flush();

        $this->addReference(self::WFA_EMAIL_TEMPLATE_NAME, $entity);
    }

    /**
     * @param ObjectManager $manager
     *
     * @return Organization
     */
    protected function getOrganization(ObjectManager $manager)
    {
        if ($this->organization) {
            return $this->organization;
        }

        $this->organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        return $this->organization;
    }

    /**
     * Get administrator user
     *
     * @param ObjectManager $manager
     *
     * @return User
     *
     * @throws \RuntimeException
     */
    protected function getAdminUser(ObjectManager $manager)
    {
        if ($this->adminUser) {
            return $this->adminUser;
        }

        $repository = $manager->getRepository('OroUserBundle:Role');
        $role = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

        if (!$role) {
            throw new \RuntimeException('Administrator role should exist.');
        }

        $user = $repository->getFirstMatchedUser($role);

        if (!$user) {
            throw new \RuntimeException(
                'Administrator user should exist to load email templates.'
            );
        }

        $this->adminUser = $user;

        return $this->adminUser;
    }
}
