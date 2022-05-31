<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Component\DoctrineUtils\ORM\ArrayKeyTrueHydrator;

/**
 * The repository for Language entity.
 */
class LanguageRepository extends ServiceEntityRepository
{
    /**
     * Returns all (or only enabled if $onlyEnabled is true) language codes as an array,
     * where the language codes are used as keys, and all values are set to boolean true:
     * <code>
     * ['en_US' => true, 'de_DE' => true, 'fr_FR' => true, ...]
     * </code>
     */
    public function getAvailableLanguageCodesAsArrayKeys(bool $onlyEnabled = false): array
    {
        $qb = $this->createQueryBuilder('language')->select('language.code');

        if ($onlyEnabled) {
            $qb->where($qb->expr()->eq('language.enabled', ':enabled'))->setParameter('enabled', true);
        }

        $query = $qb->getQuery();
        $query
            ->getEntityManager()
            ->getConfiguration()
            ->addCustomHydrationMode(ArrayKeyTrueHydrator::NAME, ArrayKeyTrueHydrator::class);

        return $query->getResult(ArrayKeyTrueHydrator::NAME);
    }

    /**
     * @param AclHelper $aclHelper
     *
     * @return array|Language[]
     */
    public function getAvailableLanguagesByCurrentUser(AclHelper $aclHelper)
    {
        $qb = $this->createQueryBuilder('language');

        return $aclHelper->apply($qb)->getResult();
    }

    /**
     * @param bool $onlyEnabled
     *
     * @return array|Language[]
     */
    public function getLanguages($onlyEnabled = false)
    {
        $qb = $this->createQueryBuilder('language');

        if ($onlyEnabled) {
            $qb->where($qb->expr()->eq('language.enabled', ':enabled'))->setParameter('enabled', true);
        }

        $qb->orderBy('language.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $languageCode
     *
     * @return array
     */
    public function getTranslationsForExport($languageCode)
    {
        $qb = $this->createQueryBuilder('l')
            ->select([
                'tk.domain as domain',
                'tk.key as key',
                't.value as value',
                't.value AS english_translation',
                '(CASE WHEN t.value IS NULL THEN 0 ELSE 1 END) as is_translated',
            ])
            ->innerJoin(TranslationKey::class, 'tk', 'WITH', '1 = 1')
            ->leftJoin(Translation::class, 't', 'WITH', 't.language = l AND t.translationKey = tk')
            ->andWhere('l.code = :languageCode')->setParameter('languageCode', $languageCode);

        return $qb->getQuery()->getArrayResult();
    }
}
