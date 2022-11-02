<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\NavigationBundle\Entity\NavigationItem;
use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Oro\Bundle\NavigationBundle\Provider\PinbarTabTitleProvider;
use Oro\Bundle\NavigationBundle\Utils\PinbarTabUrlNormalizer;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Updates PinbarTabs title and titleShort properties with actual titles.
 */
class UpdatePinbarTabUrlsAndTitles extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const BATCH_SIZE = 200;

    public function load(ObjectManager $manager)
    {
        $this->updateTitles($manager);
        $this->updateUrls($manager);
    }

    /**
     * @return string
     */
    protected function getPinbarTabClass()
    {
        return PinbarTab::class;
    }

    /**
     * @return string
     */
    protected function getNavigationItemClass()
    {
        return NavigationItem::class;
    }

    private function updateTitles(ObjectManager $manager): void
    {
        /** @var PinbarTabTitleProvider $pinbarTabTitleProvider */
        $pinbarTabTitleProvider = $this->container->get('oro_navigation.provider.pinbar_tab_title');

        /** @var EntityRepository $repo */
        $repo = $manager->getRepository($this->getPinbarTabClass());
        $qb = $repo->createQueryBuilder('pt');
        $query = $qb->where($qb->expr()->eq('pt.title', ':title'))
            ->andWhere($qb->expr()->gt('pt.id', ':fromId'))
            ->setMaxResults(self::BATCH_SIZE)
            ->orderBy('pt.id', 'ASC')
            ->getQuery();

        $id = 0;
        while ($results = $query->execute(['title' => '', 'fromId' => $id])) {
            /** @var PinbarTab $pinbarTab */
            foreach ($results as $pinbarTab) {
                $id = $pinbarTab->getId();
                [$title, $titleShort] = $pinbarTabTitleProvider->getTitles($pinbarTab->getItem());
                $pinbarTab->setTitle($title);
                $pinbarTab->setTitleShort($titleShort);
                // We flush on each record to get the proper titles for the next PinbarTab.
                $manager->flush($pinbarTab);
            }
            $manager->clear();
        }
    }

    private function updateUrls(ObjectManager $manager): void
    {
        /** @var PinbarTabUrlNormalizer $pinbarTabUrlNormalizer */
        $pinbarTabUrlNormalizer = $this->container->get('oro_navigation.utils.pinbar_tab_url_normalizer');

        $qb = $manager->getRepository($this->getNavigationItemClass())
            ->createQueryBuilder('ni')
            ->orderBy('ni.id', 'ASC');
        $qb->where($qb->expr()->eq('ni.type', ':type'))
            ->setParameter('type', 'pinbar');

        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setBufferSize(self::BATCH_SIZE);
        $iterator->setPageCallback(function () use ($manager) {
            $manager->flush();
            $manager->clear();
        });

        /** @var NavigationItem $navigationItem */
        foreach ($iterator as $navigationItem) {
            $urlNormalized = $pinbarTabUrlNormalizer->getNormalizedUrl($navigationItem->getUrl());
            $navigationItem->setUrl($urlNormalized);
        }
    }
}
