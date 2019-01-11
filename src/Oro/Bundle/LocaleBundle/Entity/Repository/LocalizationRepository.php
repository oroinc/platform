<?php

namespace Oro\Bundle\LocaleBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorInterface;
use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Doctrine repository for Localization entity
 *
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

    /**
     * @param string $languageCode
     * @param string $formattingCode
     * @return Localization|null
     */
    public function findOneByLanguageCodeAndFormattingCode(string $languageCode, string $formattingCode): ?Localization
    {
        $qb = $this->createQueryBuilder('localization');

        return $qb->innerJoin('localization.language', 'language')
            ->where(
                $qb->expr()->eq('localization.formattingCode', ':formattingCode'),
                $qb->expr()->eq('language.code', ':languageCode')
            )
            ->setParameter('formattingCode', $formattingCode)
            ->setParameter('languageCode', $languageCode)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * The application must have possibility to get available localizations data without warming Doctrine metadata.
     * It requires for building the applications cache from the scratch, because in any time the application may need to
     * get this data. But after loading Doctrine metadata for some entities, extended functionality for this entities
     * will not work.
     *
     * @return array
     */
    public function getLocalizationsData(): array
    {
        $sql = 'SELECT loc.id, loc.formatting_code AS formatting, lang.code AS language ' .
            'FROM oro_localization AS loc ' .
            'INNER JOIN oro_language AS lang ON lang.id = loc.language_id';

        $stmt = $this->getEntityManager()
            ->getConnection()
            ->executeQuery($sql);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['id']] = [
                'languageCode' => $row['language'],
                'formattingCode' => $row['formatting'],
            ];
        }

        return $result;
    }
}
