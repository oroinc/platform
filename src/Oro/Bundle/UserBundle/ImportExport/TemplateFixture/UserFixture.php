<?php

namespace Oro\Bundle\UserBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

class UserFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'Oro\Bundle\UserBundle\Entity\User';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->getEntityData('John Doo');
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($key)
    {
        return new User();
    }

    /**
     * @param string $key
     * @param User   $entity
     */
    public function fillEntityData($key, $entity)
    {
        $businessUnitRepo = $this->templateManager
            ->getEntityRepository('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit');

        switch ($key) {
            case 'John Doo':
                $entity
                    ->setUsername('admin')
                    ->setLoginCount(101)
                    ->setId(1)
                    ->setPlainPassword('admin_password1Q')
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
                    ->setOwner($businessUnitRepo->getEntity('Main'))
                    ->addGroup(new Group('Administrators'))
                    ->addBusinessUnit($businessUnitRepo->getEntity('Main'));
                return;
        }

        parent::fillEntityData($key, $entity);
    }
}
