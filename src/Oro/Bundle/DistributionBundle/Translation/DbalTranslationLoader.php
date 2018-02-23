<?php

namespace Oro\Bundle\DistributionBundle\Translation;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class DbalTranslationLoader implements LoaderInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        /** @var MessageCatalogue $catalogue */
        $catalogue = new MessageCatalogue($locale);

        $messages = array_column($this->findAllByLanguageAndDomain($locale, $domain), 'value', 'key');

        $catalogue->add($messages, $domain);

        return $catalogue;
    }

    /**
     * @param string $locale
     * @param string $domain
     *
     * @return array
     */
    protected function findAllByLanguageAndDomain($locale, $domain)
    {
        /** @var Connection $connection */
        $connection = $this->doctrine->getConnection();

        /** @var QueryBuilder $qb */
        $qb = $connection->createQueryBuilder();
        $qb->select(sprintf('DISTINCT k.%s, t.value', $connection->quoteIdentifier('key')))
            ->from('oro_translation', 't')
            ->join('t', 'oro_language', 'l', 'l.id = t.language_id')
            ->join('t', 'oro_translation_key', 'k', 'k.id = t.translation_key_id')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('l.code', ':code'),
                    $qb->expr()->gt('t.scope', ':scope'),
                    $qb->expr()->eq('k.domain', ':domain')
                )
            )
            ->setParameter('code', $locale, Type::STRING)
            ->setParameter('scope', 0, Type::INTEGER)
            ->setParameter('domain', $domain, Type::STRING);

        return $qb->execute()->fetchAll();
    }
}
