<?php

namespace Oro\Bundle\NoteBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ContactBundle\Entity\Contact;

class LoadContactData extends AbstractFixture
{
    protected $data = [
        [
            'firstName' => 'John',
            'lastName'  => 'Doe',
            'reference' => 'oro_note:contact:john_doe'
        ],
        [
            'firstName' => 'Alex',
            'lastName'  => 'Smith',
            'reference' => 'oro_note:contact:alex_smith'
        ],
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
            $contact = new Contact();
            $contact->setFirstName($data['firstName']);
            $contact->setLastName($data['lastName']);
            $contact->setOwner($admin);
            $contact->setOrganization($admin->getOrganization());

            $manager->persist($contact);

            $this->setReference($data['reference'], $contact);
        }

        $manager->flush();
    }
}
