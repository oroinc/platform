<?php

namespace Oro\Bundle\ConfigBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;

/**
 * Class ConfigValueRepository
 *
 * @package Oro\Bundle\ConfigBundle\Entity\Repository
 */
class ConfigValueRepository extends EntityRepository
{
    /**
     * Remove "values" entity depends on it's section and name identifier
     *
     * @param Config $config
     * @param array  $removed [..., ['SECTION_IDENTIFIER', 'NAME_IDENTIFIER'], ...]
     *
     * @return array
     */
    public function removeValues(Config $config, array $removed)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        $this->getEntityManager()->beginTransaction();
        foreach ($removed as $item) {
            $builder->delete('Oro\Bundle\ConfigBundle\Entity\ConfigValue', 'cv')
                ->where('cv.config = :config')
                ->andWhere('cv.name = :name')
                ->andWhere('cv.section = :section')
                ->setParameter('config', $config)
                ->setParameter('section', $item[0])
                ->setParameter('name', $item[1]);
            $builder->getQuery()->execute();
        }
        $this->getEntityManager()->commit();
    }

    /**
     * @param string $section
     */
    public function removeBySection($section)
    {
        $qb = $this->createQueryBuilder('configValue');
        $qb->delete()
            ->where($qb->expr()->eq('configValue.section', ':section'))
            ->setParameter('section', $section)
            ->getQuery()->execute();
    }

    /**
     * @param string $scope
     * @param string $section
     * @param string $name
     *
     * @return ConfigValue[]
     */
    public function getConfigValues($scope, $section, $name)
    {
        return $this->createQueryBuilder('cv')
            ->join('cv.config', 'c')
            ->where('c.scopedEntity = :entityName')
            ->andWhere('cv.section = :section')
            ->andWhere('cv.name = :name')
            ->setParameters([
                'entityName' => $scope,
                'section' => $section,
                'name' => $name,
            ])
            ->getQuery()
            ->getResult();
    }
}
