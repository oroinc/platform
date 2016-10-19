<?php

namespace Oro\Bundle\TranslationBundle\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

//todo cover DB queries by functional tests in BAP-12113
class TranslationHelper
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var TranslationManager */
    protected $translationManager;

    /** @var array */
    protected $values = [];

    /**
     * @param ManagerRegistry $registry
     * @param TranslationManager $translationManager
     */
    public function __construct(ManagerRegistry $registry, TranslationManager $translationManager)
    {
        $this->registry = $registry;
        $this->translationManager = $translationManager;
    }

    /**
     * @param string $keysPrefix
     * @param string $locale
     * @param string $domain
     * @return array
     */
    public function findValues($keysPrefix, $locale, $domain)
    {
        $queryBuilder = $this->getTranslationRepository()->createQueryBuilder('t');
        $result = $queryBuilder->select('tk.key, t.value')
            ->join('t.language', 'l')
            ->join('t.translationKey', 'tk')
            ->where('tk.domain = :domain AND l.code = :locale')
            ->andWhere($queryBuilder->expr()->like('tk.key', ':keysPrefix'))
            ->setParameters(['locale' => $locale, 'domain' => $domain, 'keysPrefix' => $keysPrefix . '%'])
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'value', 'key');
    }

    /**
     * @param string $keyPrefix
     * @param string $locale
     * @param string $domain
     */
    public function prepareValues($keyPrefix, $locale, $domain)
    {
        $cacheKey = sprintf('%s-%s', $locale, $domain);

        $this->values[$cacheKey] = $this->findValues($keyPrefix, $locale, $domain);
    }

    /**
     * @param string $key
     * @param string $locale
     * @param string $domain
     * @return string
     */
    public function getValue($key, $locale, $domain)
    {
        $cacheKey = sprintf('%s-%s', $locale, $domain);

        return isset($this->values[$cacheKey][$key]) ? $this->values[$cacheKey][$key] : $key;
    }

    /**
     * @param string $key
     * @param string $locale
     * @param string $domain
     * @return string
     */
    public function findValue($key, $locale, $domain)
    {
        $queryBuilder = $this->getTranslationRepository()
            ->getFindValueQueryBuilder($key, $locale, $domain)
            ->select('t.value');

        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        return $result ? $result['value'] : $key;
    }

    /**
     * @return TranslationRepository
     */
    protected function getTranslationRepository()
    {
        return $this->registry->getManagerForClass(Translation::class)->getRepository(Translation::class);
    }
}
