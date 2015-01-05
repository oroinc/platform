<?php

namespace Oro\Bundle\CommentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\NoteBundle\Entity\Note;

class LoadCommentData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user         = $manager->getRepository('OroUserBundle:User')->findOneByUsername('admin');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        $note = new Note();

        $note->setOwner($user);
        $note->setCreatedAt(new \DateTime('now'));
        $note->setOrganization($organization);
        $note->setMessage('test note');

        $manager->persist($note);

        $this->setReference('default_note', $note);

        $manager->flush();


    }
}
