<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateWithScopeChangeEvent;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Sync inherited URIs with global URI.
 */
class UpdateBrokenInheritedUris extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function load(ObjectManager $manager)
    {
        $repo = $this->getRepository($manager);

        $menusByScopes = [];
        foreach ($this->getBrokenMenuUpdates($repo) as $menuUpdate) {
            $menuName = $menuUpdate->getMenu();
            $repo->updateDependentMenuUpdates($menuUpdate);
            foreach ($repo->getDependentMenuUpdateScopes($menuUpdate) as $scope) {
                $menusByScopes[$menuName][$scope->getId()] = $scope;
            }
        }

        $this->clearCaches($menusByScopes);
    }

    /**
     * @param ObjectManager $manager
     * @return \Doctrine\Persistence\ObjectRepository|MenuUpdateRepository
     */
    protected function getRepository(ObjectManager $manager)
    {
        return $manager->getRepository(MenuUpdate::class);
    }

    /**
     * @param MenuUpdateRepository $repo
     * @return \Iterator|MenuUpdate[]
     */
    protected function getBrokenMenuUpdates(MenuUpdateRepository $repo)
    {
        $scopeManager = $this->container->get(ScopeManager::class);
        $globalScope = $scopeManager->find('menu_default_visibility', []);

        $subSelect = $repo->createQueryBuilder('u2');
        $subSelect->select('u2.id')
            ->where(
                $subSelect->expr()->andX(
                    $subSelect->expr()->eq('u.menu', 'u2.menu'),
                    $subSelect->expr()->eq('u.key', 'u2.key'),
                    $subSelect->expr()->neq('u.id', 'u2.id'),
                    $subSelect->expr()->neq('CAST(u.uri as text)', 'CAST(u2.uri as text)')
                )
            );

        $qb = $repo->createQueryBuilder('u');
        $qb->where($qb->expr()->exists($subSelect->getDQL()))
            ->andWhere($qb->expr()->eq('u.scope', ':scope'))
            ->setParameter('scope', $globalScope);

        return new BufferedIdentityQueryResultIterator($qb);
    }

    protected function clearCaches(array $menusByScopes): void
    {
        $eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        foreach ($menusByScopes as $menuName => $scopes) {
            foreach ($scopes as $scope) {
                $eventDispatcher->dispatch(
                    new MenuUpdateWithScopeChangeEvent($menuName, $scope),
                    MenuUpdateWithScopeChangeEvent::NAME
                );
            }
        }
    }
}
