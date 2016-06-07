<?php

namespace Oro\Bundle\LocaleBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * @method Localization|null findOneByLanguageCode($code)
 */
class LocalizationRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function getLanguageCodes()
    {
        return $this->createQueryBuilder('l')
            ->select('l.languageCode')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @return array
     */
    public function findRootsWithChildren()
    {
        $localizations = $this->createQueryBuilder('l')
            ->addSelect('children')
            ->leftJoin('l.childLocalizations', 'children')
            ->getQuery()
            ->execute();
        
        return array_filter($localizations, function (Localization $localization) {
            return !$localization->getParentLocalization();
        });
    }

    /**
     * @return int
     */
    public function getLocalizationsCount()
    {
        return (int)$this->createQueryBuilder('l')
            ->select('COUNT(l.id) as localizationsCount')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
