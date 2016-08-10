<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class LanguageRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function getAvailableLanguageCodes()
    {
        $codes = $this->createQueryBuilder('language')->select('language.code')->getQuery()->getArrayResult();

        return array_map(
            function (array $row) {
                return $row['code'];
            },
            $codes
        );
    }
}
