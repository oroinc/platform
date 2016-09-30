<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\UserBundle\Entity\User;

class DefaultMenuUpdateProvider extends AbstractMenuUpdateProvider
{
    /**
     * {@inheritdoc}
     */
    public function getUpdates($menu)
    {
        /** @var MenuUpdateRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroNavigationBundle:MenuUpdate');

        $organization = $this->getCurrentOrganization();
        $user = $this->getCurrentUser();

        return $repository->getMenuUpdates($menu, $organization, $user);
    }

    /**
     * @return null|User
     */
    private function getCurrentUser()
    {
        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }
}
