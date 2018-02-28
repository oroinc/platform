<?php

namespace Oro\Bundle\CommentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCommentData extends AbstractCommentFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var array CommentData
     */
    protected $commentData = [
        [
            'message' => 'First comment',
            'createdAt' => 'now -5 days',
            'updatedAt' => 'now -5 days',
        ],
        [
            'message' => 'Second comment',
            'createdAt' => 'now -4 days',
            'updatedAt' => 'now -4 days',
        ],
        [
            'message' => 'Third comment',
            'createdAt' => 'now +3 days',
            'updatedAt' => 'now +5 days',
        ],
    ];

    /**
     * @var ContainerInterface
     */
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
    public function getDependencies()
    {
        return ['Oro\Bundle\CommentBundle\Tests\Functional\DataFixtures\LoadEmailData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $adminUser = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        foreach ($this->commentData as $data) {
            $entity = new Comment();
            $entity->setTarget($this->getReference('default_activity'));
            $entity->setOrganization($organization);
            $entity->setOwner($adminUser);
            $this->setEntityPropertyValues($entity, $data, ['createdAt', 'updatedAt']);
            $entity->setCreatedAt(new \DateTime($data['createdAt'], new \DateTimeZone('UTC')));
            $entity->setUpdatedAt(new \DateTime($data['updatedAt'], new \DateTimeZone('UTC')));
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
