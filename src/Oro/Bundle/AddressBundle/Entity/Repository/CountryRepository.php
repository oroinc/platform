<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Oro\Bundle\AddressBundle\Entity\Country;

/**
 * Entity repository for Country dictionary.
 */
class CountryRepository extends EntityRepository implements IdentityAwareTranslationRepositoryInterface
{
    /**
     * @return Country[]
     */
    public function getCountries()
    {
        $query = $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery();

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);

        return $query->execute();
    }

    /**
     * @return array
     */
    public function getAllCountryNamesArray()
    {
        return $this->createQueryBuilder('c')
            ->select('c.iso2Code, c.iso3Code, c.name')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return array
     */
    public function getAllIdentities()
    {
        $result = $this->createQueryBuilder('c')
            ->select('c.iso2Code')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'iso2Code');
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
            $qb = $this->createQueryBuilder('c');
            $qb->select('c.iso2Code', 'c.name')
                ->where($qb->expr()->in('c.iso2Code', ':iso2Code'))
                ->setParameter('iso2Code', array_keys($data));

            $result = $qb->getQuery()->getArrayResult();

            foreach ($result as $country) {
                $value = $data[$country['iso2Code']];

                if ($country['name'] !== $value) {
                    $connection->update(
                        $this->getClassMetadata()->getTableName(),
                        ['name' => $value],
                        ['iso2_code' => $country['iso2Code']]
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
