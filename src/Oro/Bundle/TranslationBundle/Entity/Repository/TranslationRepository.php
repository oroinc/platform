<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class TranslationRepository extends EntityRepository
{
    /**
     * @param string $key
     * @param string $locale
     * @param string $domain
     * @return array
     */
    public function findValue($key, $locale, $domain = 'messages')
    {
        return $this->findOneBy(
            [
                'key'    => $key,
                'locale' => $locale,
                'domain' => $domain
            ]
        );
    }

    public function findValues($locale, $domain = 'messages')
    {
        return $this->findBy(
            [
                'locale' => $locale,
                'domain' => $domain
            ]
        );
    }
}
