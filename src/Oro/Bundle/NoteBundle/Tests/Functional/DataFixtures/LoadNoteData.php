<?php

namespace Oro\Bundle\NoteBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\NoteBundle\Entity\Note;

class LoadNoteData extends AbstractFixture implements DependentFixtureInterface
{
    protected $data = [
        [
            'message' => 'Foo note',
            'targets' => [
                'oro_note:account:john_doe'
            ],
            'reference' => 'oro_note:note:foo'
        ],
        [
            'message' => 'Bar note',
            'targets' => [
                'oro_note:contact:alex_smith',
                'oro_note:account:john_doe',
                'oro_note:account:alex_smith',
            ],
            'reference' => 'oro_note:note:bar'
        ],
        [
            'message' => 'Baz note',
            'targets' => [
                'oro_note:contact:alex_smith',
                'oro_note:account:john_doe',
            ],
            'reference' => 'oro_note:note:baz'
        ]
    ];


    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $admin = $manager
            ->getRepository('OroUserBundle:User')
            ->findOneBy(['username' => 'admin']);

        foreach ($this->data as $data) {
            $note = new Note();
            $note->setMessage($data['message']);
            $note->setOwner($admin);
            $note->setOrganization($admin->getOrganization());

            foreach ($data['targets'] as $target) {
                $note->addActivityTarget($this->getReference($target));
            }

            $manager->persist($note);

            $this->setReference($data['reference'], $note);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadContactData::class,
            LoadAccountData::class
        ];
    }
}
