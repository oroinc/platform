<?php

namespace Oro\Bundle\UserBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provides template fixture data for user import/export operations.
 *
 * This fixture class generates sample user data for import/export templates,
 * allowing users to understand the expected format and structure when importing
 * or exporting user records.
 */
class UserFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    #[\Override]
    public function getEntityClass()
    {
        return 'Oro\Bundle\UserBundle\Entity\User';
    }

    #[\Override]
    public function getData()
    {
        return $this->getEntityData('John Doo');
    }

    #[\Override]
    protected function createEntity($key)
    {
        return new User();
    }

    /**
     * @param string $key
     * @param User   $entity
     */
    #[\Override]
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
                    ->setOwner($businessUnitRepo->getEntity('Main'))
                    ->addGroup(new Group('Administrators'))
                    ->addBusinessUnit($businessUnitRepo->getEntity('Main'));
                return;
        }

        parent::fillEntityData($key, $entity);
    }
}
