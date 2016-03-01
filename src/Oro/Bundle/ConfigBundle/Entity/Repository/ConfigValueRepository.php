<?php

namespace Oro\Bundle\ConfigBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Entity\Config;

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
}
