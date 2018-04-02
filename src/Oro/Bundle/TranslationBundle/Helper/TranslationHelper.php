<?php

namespace Oro\Bundle\TranslationBundle\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;

class TranslationHelper
{
    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $keysPrefix
     * @param string $locale
     * @param string $domain
     * @return array
     */
    public function findValues($keysPrefix, $locale, $domain)
    {
        return $this->getTranslationRepository()->findValues($keysPrefix, $locale, $domain);
    }

    /**
     * @param string $key
     * @param string $locale
     * @param string $domain
     * @return string|null
     */
    public function findValue($key, $locale, $domain)
    {
        $translation = $this->getTranslationRepository()->findTranslation($key, $locale, $domain);

        return $translation ? $translation->getValue() : null;
    }

    /**
     * @return TranslationRepository
     */
    protected function getTranslationRepository()
    {
        return $this->registry->getManagerForClass(Translation::class)->getRepository(Translation::class);
    }
}
