<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Entity\Repository\HistoryItemRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class NavigationHistoryItemData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const NAVIGATION_HISTORY_ITEM_1 = 'First navigation history item';
    const NAVIGATION_HISTORY_ITEM_2 = 'Second navigation history item';
    const NAVIGATION_HISTORY_ITEM_3 = 'Third navigation history item';
    const NAVIGATION_HISTORY_ITEM_4 = 'Fourth navigation history item';
    const NAVIGATION_HISTORY_ITEM_5 = 'Fifth navigation history item';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        /** @var HistoryItemRepository $repo */
        $repo = $this->container->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(NavigationHistoryItem::class);

        $repo->createQueryBuilder('i')->delete()->getQuery()->execute();

        $qb = $repo->createQueryBuilder('i');
        $query = $qb->update()
            ->set('i.visitedAt', ':visitedAt')
            ->set('i.visitCount', ':visitCount')
            ->where($qb->expr()->eq('i.id', ':itemId'))
            ->getQuery();

        foreach ($this->getData() as $itemData) {
            $item = new NavigationHistoryItem();
            $item->setUrl('/admin/user/view/' . $user->getId());
            $item->setUser($user);
            $item->setOrganization($user->getOrganization());
            $item->setRoute('oro_user_view');
            $item->setEntityId($user->getId());

            $item->setTitle($itemData['title']);

            $manager->persist($item);
            $manager->flush();

            $query->execute([
                'itemId' => $item->getId(),
                'visitedAt' => $itemData['visitedAt'],
                'visitCount' => $itemData['visitCount'],
            ]);
            $manager->refresh($item);

            $this->addReference($itemData['title'], $item);
        }
    }

    /**
     * @return array
     */
    protected function getData()
    {
        $utc = new \DateTimeZone('UTC');

        return [
            [
                'title' => self::NAVIGATION_HISTORY_ITEM_1,
                'visitedAt' => new \DateTime('midnight - 2 weeks', $utc),
                'visitCount' => 1,
            ],
            [
                'title' => self::NAVIGATION_HISTORY_ITEM_2,
                'visitedAt' => new \DateTime('midnight - 1 week', $utc),
                'visitCount' => 2,
            ],
            [
                'title' => self::NAVIGATION_HISTORY_ITEM_3,
                'visitedAt' => new \DateTime('midnight', $utc),
                'visitCount' => 3,
            ],
            [
                'title' => self::NAVIGATION_HISTORY_ITEM_4,
                'visitedAt' => new \DateTime('midnight + 1 week', $utc),
                'visitCount' => 4,
            ],
            [
                'title' => self::NAVIGATION_HISTORY_ITEM_5,
                'visitedAt' => new \DateTime('midnight + 2 weeks', $utc),
                'visitCount' => 5,
            ],
        ];
    }
}
