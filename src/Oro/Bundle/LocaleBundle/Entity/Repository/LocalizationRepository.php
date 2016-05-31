<?php

namespace Oro\Bundle\LocaleBundle\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizationRepository extends EntityRepository
{
    /**
     * Returns list of available parents for localization instance
     *
     * @param Localization $localization
     *
     * @return array
     */
    public function getAvailableParents(Localization $localization = null)
    {
        $localizations = $this->findAll();

        if (!($localization instanceof Localization) || (!$localization->getId())) {
            return $localizations;
        }

        $collection = new ArrayCollection();

        /** @var Localization $localizationItem */
        foreach ($localizations as $localizationItem) {
            if (!$collection->contains($localizationItem)) {
                if ($localizationItem->getId() !== $localization->getId()) {
                    $collection->add($localizationItem);
                }
            }
        }

        $collection = $this->removeChildLocalizationsRecursive($localization, $collection);

        return $collection->toArray();
    }

    /**
     * Removes all child localization at any level of hierarchy
     *
     * @param Localization $localization
     * @param ArrayCollection $collection
     * @return ArrayCollection
     */
    private function removeChildLocalizationsRecursive(Localization $localization, ArrayCollection $collection)
    {
        $childLocalizations = $localization->getChildLocalizations();
        if (count($childLocalizations)) {
            foreach ($childLocalizations as $childLocalization) {
                if ($childLocalization->getChildLocalizations()) {
                    $collection = $this->removeChildLocalizationsRecursive($childLocalization, $collection);
                }
                $collection->removeElement($childLocalization);
            }
        }

        return $collection;
    }
}
