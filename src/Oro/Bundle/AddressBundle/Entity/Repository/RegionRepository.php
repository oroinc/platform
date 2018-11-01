<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TranslationBundle\Translation\TranslatableQueryTrait;

/**
 * Entity repository for Region dictionary.
 */
class RegionRepository extends EntityRepository implements IdentityAwareTranslationRepositoryInterface
{
    use TranslatableQueryTrait;

    /**
     * @param Country $country
     * @return QueryBuilder
     */
    public function getCountryRegionsQueryBuilder(Country $country)
    {
        return $this->createQueryBuilder('r')
            ->where('r.country = :country')
            ->orderBy('r.name', 'ASC')
            ->setParameter('country', $country);
    }

    /**
     * @param Country $country
     * @return Region[]
     */
    public function getCountryRegions(Country $country)
    {
        $query = $this->getCountryRegionsQueryBuilder($country)->getQuery();
        $query->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );
        $this->addTranslatableLocaleHint($query, $this->getEntityManager());

        return $query->execute();
    }

    /**
     * @return array
     */
    public function getAllIdentities()
    {
        $result = $this->createQueryBuilder('r')
            ->select('r.combinedCode')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'combinedCode');
    }


    /**
     * {@inheritdoc}
     */
    public function updateTranslations(array $data)
    {
        if (!$data) {
            return;
        }

        $connection = $this->getEntityManager()->getConnection();
        $connection->beginTransaction();

        try {
            $qb = $this->createQueryBuilder('r');
            $qb->select('r.combinedCode', 'r.name')
                ->where($qb->expr()->in('r.combinedCode', ':combinedCode'))
                ->setParameter('combinedCode', array_keys($data));

            $result = $qb->getQuery()->getArrayResult();

            foreach ($result as $region) {
                $value = $data[$region['combinedCode']];

                if ($region['name'] !== $value) {
                    $connection->update(
                        $this->getClassMetadata()->getTableName(),
                        ['name' => $value],
                        ['combined_code' => $region['combinedCode']]
                    );
                }
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();

            throw $e;
        }
    }
}
