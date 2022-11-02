<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadUserEmailData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $userManager = $this->container->get('oro_user.manager');

        $email = new Email();
        $email->setEmail('simple_user@example.org');

        /** @var UserInterface $user */
        $user = $this->getReference('simple_user');
        $user->addEmail($email);
        $userManager->updateUser($user);

        $this->setReference('simple_user_email', $email);
    }
}
