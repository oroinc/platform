<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\UserBundle\Entity\User;

class LoadAuditData extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $manager->getRepository('OroUserBundle:User')->findOneBy(['username' => 'admin']);

        $logEntry = new Audit();
        $logEntry->setAction('update');
        $logEntry->setObjectClass('Oro\Bundle\UserBundle\Entity\User');
        $logEntry->setLoggedAt();
        $logEntry->setUser($user);
        $logEntry->setOrganization($user->getOrganization());
        $logEntry->setObjectName('test_user');
        $logEntry->setObjectId($user->getId());
        $logEntry->createField('username', 'text', 'new_value', 'old_value');
        $logEntry->setVersion(1);

        $manager->persist($logEntry);
        $manager->flush();

        $this->setReference('audit_log_entry', $logEntry);
    }
}
