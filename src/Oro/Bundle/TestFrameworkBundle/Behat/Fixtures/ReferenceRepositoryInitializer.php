<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManager;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use OroEntityProxy\OroEmailBundle\EmailAddressProxy;
use Symfony\Component\HttpKernel\KernelInterface;

class ReferenceRepositoryInitializer
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var AliceCollection
     */
    protected $referenceRepository;

    /**
     * @param Registry $registry
     * @param AliceCollection $referenceRepository
     */
    public function __construct(KernelInterface $kernel, AliceCollection $referenceRepository)
    {
        $this->kernel = $kernel;
        $this->referenceRepository = $referenceRepository;
    }

    /**
     * Load references to repository
     */
    public function init()
    {
        $this->referenceRepository->clear();

        try {
            $user = $this->getDefaultUser();
            $userRole = $this->getRole(User::ROLE_DEFAULT);
            $adminRole = $this->getRole(User::ROLE_ADMINISTRATOR);
        } catch (TableNotFoundException $e) {
            // Schema is not initialized yet
            return;
        }

        $this->referenceRepository->set('admin', $user);
        $this->referenceRepository->set('userRole', $userRole);
        $this->referenceRepository->set('adminRole', $adminRole);
        $this->referenceRepository->set('organization', $user->getOrganization());
        $this->referenceRepository->set('business_unit', $user->getOwner());
        $this->referenceRepository->set(
            'adminEmailAddress',
            $this->getEntityManager()->getRepository(EmailAddressProxy::class)->findOneBy([])
        );
        $this->referenceRepository->set('defaultProductFamily', $this->getDefaultProductFamily());
        $this->referenceRepository->set(
            'en_language',
            $this->getEntityManager()->getRepository(Language::class)->findOneBy(['code' => 'en'])
        );
    }

    /**
     * Remove all references from repository
     */
    public function clear()
    {
        $this->referenceRepository->clear();
    }

    public function getRole($role)
    {
        return $this->getEntityManager()->getRepository(Role::class)->findOneBy(['role' => $role]);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return User
     * @throws \InvalidArgumentException
     */
    protected function getDefaultUser()
    {
        /** @var RoleRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroUserBundle:Role');
        $role       = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

        if (!$role) {
            throw new \InvalidArgumentException('Administrator role should exist.');
        }

        $user = $repository->getFirstMatchedUser($role);

        if (!$user) {
            throw new \InvalidArgumentException(
                'Administrator user should exist.'
            );
        }

        return $user;
    }

    /**
     * @return AttributeFamily
     * @throws \InvalidArgumentException
     */
    protected function getDefaultProductFamily()
    {
        $repository = $this->getEntityManager()->getRepository(AttributeFamily::class);
        $attributeFamily = $repository->findOneBy([
            'code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE,
        ]);

        if (!$attributeFamily) {
            throw new \InvalidArgumentException('Default product attribute family should exist.');
        }

        return $attributeFamily;
    }
}
