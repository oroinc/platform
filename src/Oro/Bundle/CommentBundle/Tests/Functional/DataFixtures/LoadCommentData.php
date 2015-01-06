<?php

namespace Oro\Bundle\CommentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EmailBundle\Entity\Email;

class LoadCommentData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $email = new Email();
        $manager->persist($email);
        $this->setReference('default_activity', $email);
        $manager->flush();
    }
}
