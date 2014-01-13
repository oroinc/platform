<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TranslationBundle\Entity\Translation;

class TranslationRepository extends EntityRepository
{
    const DEFAULT_DOMAIN = 'messages';

    /**
     * @param string $key
     * @param string $locale
     * @param string $domain
     *
     * @return Translation
     */
    public function findValue($key, $locale, $domain = self::DEFAULT_DOMAIN)
    {
        return $this->findOneBy(
            [
                'key'    => $key,
                'locale' => $locale,
                'domain' => $domain
            ]
        );
    }

    /**
     * @param        $locale
     * @param string $domain
     *
     * @return Translation[]
     */
    public function findValues($locale, $domain = self::DEFAULT_DOMAIN)
    {
        return $this->findBy(
            [
                'locale' => $locale,
                'domain' => $domain
            ]
        );
    }
}
