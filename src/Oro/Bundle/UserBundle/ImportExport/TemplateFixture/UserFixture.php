<?php

namespace Oro\Bundle\UserBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

class UserFixture implements TemplateFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $group = new Group('Administrators');

        $unit = new BusinessUnit();
        $unit->setName('Main');

        $user = new User();
        $user
            ->setUsername('admin')
            ->setLoginCount(101)
            ->setId(1)
            ->setPlainPassword('admin_password')
            ->setFirstname('John')
            ->setMiddleName('Awesome')
            ->setLastname('Doe')
            ->setEmail('admin@example.com')
            ->setNamePrefix('Mr.')
            ->setNameSuffix('Jr.')
            ->setBirthday(new \DateTime('2013-02-01'))
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->setEnabled(true)
            ->setOwner($unit)
            ->addGroup($group)
            ->addBusinessUnit($unit);

        return new \ArrayIterator(array($user));
    }
}
