<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadEmailTemplateData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const NO_ENTITY_NAME_TEMPLATE_REFERENCE = 'emailTemplate1';
    const NOT_SYSTEM_TEMPLATE_REFERENCE = 'emailTemplate2';
    const NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE = 'emailTemplate3';
    const SYSTEM_WITH_ENTITY_TEMPLATE_REFERENCE = 'emailTemplate4';
    const NOT_SYSTEM_NO_ENTITY_TEMPLATE_REFERENCE = 'emailTemplate5';
    const SYSTEM_NOT_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE = 'emailTemplate6';
    const NOT_SYSTEM_NOT_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE = 'emailTemplate7';
    const SYSTEM_FAIL_TO_COMPILE = 'emailTemplate8';
    const OWNER_USER_REFERENCE = 'simple_user';
    const ENTITY_NAME = 'Entity\Name';

    /** @var ContainerInterface */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $owner = $this->getReference(self::OWNER_USER_REFERENCE);

        $emailTemplate1 = new EmailTemplate('no_entity_name', 'test Application, etc.');
        $emailTemplate1->setOrganization($owner->getOrganization());
        $emailTemplate2 = new EmailTemplate('test_template', 'test Application, etc');
        $emailTemplate2->setEntityName(User::class);
        $emailTemplate2->setOrganization($owner->getOrganization());

        $emailTemplate3 = new EmailTemplate('no_system', 'test Application, etc');
        $emailTemplate3->setIsSystem(false);
        $emailTemplate3->setEntityName(self::ENTITY_NAME);
        $emailTemplate3->setOrganization($owner->getOrganization());

        $emailTemplate4 = new EmailTemplate('system', 'test Application, etc');
        $emailTemplate4->setIsSystem(true);
        $emailTemplate4->setEntityName(self::ENTITY_NAME);
        $emailTemplate4->setOrganization($owner->getOrganization());

        $emailTemplate5 = new EmailTemplate('no_system_no_entity', 'test Application, etc');
        $emailTemplate5->setIsSystem(false);
        $emailTemplate5->setOrganization($owner->getOrganization());

        $emailTemplate6 = new EmailTemplate('system_not_visible', 'test Application etc');
        $emailTemplate6->setIsSystem(true);
        $emailTemplate6->setVisible(false);
        $emailTemplate6->setEntityName(self::ENTITY_NAME);
        $emailTemplate6->setOrganization($owner->getOrganization());

        $emailTemplate7 = new EmailTemplate('not_system_not_visible', 'test Application etc');
        $emailTemplate7->setIsSystem(false);
        $emailTemplate7->setVisible(false);
        $emailTemplate7->setEntityName(self::ENTITY_NAME);
        $emailTemplate7->setOrganization($owner->getOrganization());

        $emailTemplate8 = new EmailTemplate('system_fail_to_compile', 'test {{ entity.notExistingProperty }} etc');
        $emailTemplate8->setIsSystem(true);
        $emailTemplate8->setOrganization($owner->getOrganization());

        $manager->persist($emailTemplate1);
        $manager->persist($emailTemplate2);
        $manager->persist($emailTemplate3);
        $manager->persist($emailTemplate4);
        $manager->persist($emailTemplate5);
        $manager->persist($emailTemplate6);
        $manager->persist($emailTemplate7);
        $manager->persist($emailTemplate8);
        $manager->flush();

        $this->setReference('emailTemplate1', $emailTemplate1);
        $this->setReference('emailTemplate2', $emailTemplate2);
        $this->setReference('emailTemplate3', $emailTemplate3);
        $this->setReference('emailTemplate4', $emailTemplate4);
        $this->setReference('emailTemplate5', $emailTemplate5);
        $this->setReference('emailTemplate6', $emailTemplate6);
        $this->setReference('emailTemplate7', $emailTemplate7);
        $this->setReference('emailTemplate8', $emailTemplate8);
    }
}
