<?php

namespace CommentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Bundle\CommentBundle\Tests\Functional\DataFixtures\AbstractCommentFixture;
use Oro\Bundle\NoteBundle\Entity\Note;

class LoadNoteData extends AbstractCommentFixture implements ContainerAwareInterface
{
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
    public function load(ObjectManager $manager)
    {
        $note = new Note();
        $note->setMessage('test_message');
        $manager->persist($note);
        $this->setReference('default_activity', $note);
        $manager->flush();
    }
}
