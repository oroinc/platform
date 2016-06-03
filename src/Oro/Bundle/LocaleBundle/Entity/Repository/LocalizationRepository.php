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
        $qb = $this->createQueryBuilder('l');

        return $qb
            ->select('l.languageCode')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @return mixed
     */
    public function findRootsWithChildren()
    {
        $localizations = $this->createQueryBuilder('l')
            ->addSelect('children')
            ->leftJoin('l.childs', 'children')
            ->getQuery()->execute();
        return array_filter($localizations, function (Localization $localization) {
            return !$localization->getParent();
        });
    }
}
