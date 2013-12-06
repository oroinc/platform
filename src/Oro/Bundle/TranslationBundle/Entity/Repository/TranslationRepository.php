<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class TranslationRepository extends EntityRepository
{
    /**
     * @param $key
     * @param $locale
     * @param string $domain
     * @return array
     */
    public function findValue($key, $locale, $domain = 'messages')
    {
        return $this->findBy(
            [
                'key'    => $key,
                'locale' => $locale,
                'domain' => $domain
            ]
        );
    }
}
