<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;

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
}
