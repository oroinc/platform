<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class LanguageRepository extends EntityRepository
{
    /**
     * @param bool $onlyEnabled
     * @return array
     */
    public function getAvailableLanguageCodes($onlyEnabled = false)
    {
        $qb = $this->createQueryBuilder('language')->select('language.code');

        if ($onlyEnabled) {
            $qb->where($qb->expr()->eq('language.enabled', ':enabled'))->setParameter('enabled', true);
        }

        $data = $qb->getQuery()->getArrayResult();

        return array_column($data, 'code');
    }
}
