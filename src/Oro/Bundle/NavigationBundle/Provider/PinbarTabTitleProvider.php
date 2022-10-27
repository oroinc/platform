<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NavigationBundle\Entity\AbstractNavigationItem;
use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository;

/**
 * Provides deduplicated title and short title for PinbarTab entity from NavigationItem title.
 */
class PinbarTabTitleProvider implements PinbarTabTitleProviderInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var TitleService */
    private $titleService;

    public function __construct(DoctrineHelper $doctrineHelper, TitleService $titleService)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->titleService = $titleService;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitles(AbstractNavigationItem $navigationItem, string $className = PinbarTab::class): array
    {
        if (!$navigationItem->getTitle()) {
            return ['', ''];
        }

        $title = $this->titleService->render([], $navigationItem->getTitle(), null, null, true);
        $titleShort = $this->titleService->render([], $navigationItem->getTitle(), null, null, true, true);

        /** @var PinbarTabRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepositoryForClass($className);
        $user = $navigationItem->getUser();
        $org = $navigationItem->getOrganization();

        $index = $repo->countPinbarTabDuplicatedTitles($titleShort, $user, $org);

        if (!$index) {
            return [$title, $titleShort];
        }

        do {
            $index++;
            $titleWithIndex = sprintf('%s (%s)', $title, $index);
            $titleShortWithIndex = sprintf('%s (%s)', $titleShort, $index);
        } while ($repo->countPinbarTabDuplicatedTitles($titleShortWithIndex, $user, $org));

        return [$titleWithIndex, $titleShortWithIndex];
    }
}
