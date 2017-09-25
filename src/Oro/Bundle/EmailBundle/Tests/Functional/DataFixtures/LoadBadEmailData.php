<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Nelmio\Alice\Persister\Doctrine;

use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;

class LoadBadEmailData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadUserData',];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        if (!$container) {
            return;
        }

        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $om)
    {
        $kernel = $this->container->get('kernel');
        $loader = $this->container->get('oro_test.alice_fixtures_loader');

        $admin = $om->getRepository('OroUserBundle:User')->findOneByEmail('admin@example.com');
        $organization = $om->getRepository('OroOrganizationBundle:Organization')->getFirst();

        $loader->setReferences([
            'admin' => $admin,
            'organization' => $organization,
        ]);

        $objects = $loader->load($kernel->locateResource('@OroEmailBundle/Tests/Functional/DataFixtures/Data/bad-emails.yml'));

        $persister = new Doctrine($om);
        $persister->persist($objects);

        $om->flush();
    }
}
