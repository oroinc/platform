<?php

namespace Oro\Bundle\NoteBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AccountBundle\Entity\Account;

class LoadAccountData extends AbstractFixture
{
    protected $data = [
        [
            'name' => 'John Doe',
            'reference' => 'oro_note:account:john_doe'
        ],
        [
            'name' => 'Alex Smith',
            'reference' => 'oro_note:account:alex_smith'
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
            $account = new Account();
            $account->setName($data['name']);
            $account->setOwner($admin);
            $account->setOrganization($admin->getOrganization());

            $manager->persist($account);

            $this->setReference($data['reference'], $account);
        }

        $manager->flush();
    }
}
