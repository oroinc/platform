<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Translation;

/**
 * A service to load translations updated by a user.
 */
class DynamicTranslationLoader implements DynamicTranslationLoaderInterface
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function loadTranslations(array $locales, bool $includeSystem): array
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Translation::class);
        $em->getConfiguration()
            ->addCustomHydrationMode(DynamicTranslationHydrator::class, DynamicTranslationHydrator::class);

        $qb = $em->createQueryBuilder()
            ->from(Translation::class, 't')
            ->select('l.code AS locale, k.domain, k.key, t.value')
            ->innerJoin('t.language', 'l')
            ->innerJoin('t.translationKey', 'k')
            ->where('l.code IN (:locales)')
            ->setParameter('locales', $locales)
            ->orderBy('t.scope', 'DESC');
        if (!$includeSystem) {
            $qb
                ->andWhere('t.scope > :scope')
                ->setParameter('scope', Translation::SCOPE_SYSTEM);
        }

        return $qb->getQuery()->getResult(DynamicTranslationHydrator::class);
    }
}
