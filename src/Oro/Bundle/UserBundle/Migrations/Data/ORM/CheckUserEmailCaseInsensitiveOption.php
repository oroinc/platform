<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Enable case insensitive option for environment with MySql
 * and case insensitive collation for email field of User table.
 */
class CheckUserEmailCaseInsensitiveOption extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository(User::class);
        if (!$repository->isCaseInsensitiveCollation()) {
            return;
        }

        $configManager = $this->container->get('oro_config.global');
        $configManager->set('oro_user.case_insensitive_email_addresses_enabled', true);
        $configManager->flush();
    }
}
