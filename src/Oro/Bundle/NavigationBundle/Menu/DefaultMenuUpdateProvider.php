<?php
namespace Oro\Bundle\NavigationBundle\Menu;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;

class DefaultMenuUpdateProvider implements MenuUpdateProviderInterface
{
    /** @var SecurityFacade  */
    protected $securityFacade;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * DefaultMenuUpdateProvider constructor.
     * @param SecurityFacade $securityFacade
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        SecurityFacade $securityFacade,
        DoctrineHelper $doctrineHelper
    ) {
        $this->securityFacade = $securityFacade;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdates($menu)
    {
        /** @var MenuUpdateRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroNavigationBundle:MenuUpdate');
        $organization = $this->securityFacade->getOrganization();
        $currentUser = $this->getCurrentUser();
        $currentBusinessUnit = $this->getCurrentBusinessUnit($organization);

        $repository->getMenuUpdates($menu, $organization, $currentBusinessUnit, $currentUser);
    }

    /**
     * @param Organization $organization
     *
     * @return null|BusinessUnit
     */
    protected function getCurrentBusinessUnit(Organization $organization)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return null;
        }

        return $user->getBusinessUnits()
            ->filter(function (BusinessUnit $businessUnit) use ($organization) {
                return $businessUnit->getOrganization()->getId() === $organization->getId();
            })
            ->first();
    }

    /**
     * @return null|User
     */
    protected function getCurrentUser()
    {
        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }
}
