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
        $data = $this->createQueryBuilder('language')->select('language.code')->getQuery()->getArrayResult();

        return array_column($data, 'code');
    }
}
