<?php

namespace Oro\Bundle\LocaleBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorInterface;
use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * @method Localization|null findOneByName($name)
 */
class LocalizationRepository extends EntityRepository implements BatchIteratorInterface
{
    use BatchIteratorTrait;
    
    /**
     * @return array
     */
    public function getNames()
    {
        $qb = $this->createQueryBuilder('l');

        return $qb
            ->select('l.name')
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
