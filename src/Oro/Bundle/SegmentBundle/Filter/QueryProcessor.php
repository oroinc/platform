<?php

namespace Oro\Bundle\SegmentBundle\Filter;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationQueryConverter;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;

class QueryProcessor
{
    /** @var RestrictionBuilder */
    protected $restrictionBuilder;

    /** @var EntityManager */
    protected $em;

    /** @var Manager */
    protected $manager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param RestrictionBuilder $restrictionBuilder
     * @param Manager            $manager
     * @param ManagerRegistry    $doctrine
     */
    public function __construct(
        RestrictionBuilder $restrictionBuilder,
        Manager $manager,
        ManagerRegistry $doctrine
    ) {
        $this->restrictionBuilder = $restrictionBuilder;
        $this->manager            = $manager;
        $this->doctrine           = $doctrine;
        $this->em                 = $doctrine->getManager();
    }

    public function process($entityName, Segment $segment)
    {
        $converter = new DatagridConfigurationQueryConverter($this->manager, $this->doctrine);
        $config    = $converter->convert('testName', $segment);

        $classMetadata = $this->em->getClassMetadata($entityName);
        $identifiers   = $classMetadata->getIdentifier();
        // only not composite identifiers are supported
        $identifier = reset($identifiers);

        $qb    = $this->em->createQueryBuilder();
        $ds    = new GroupingOrmFilterDatasourceAdapter($qb);
        $alias = $ds->generateParameterName($identifier);
        $qb->select(sprintf('%s.%s', $alias, $identifier))
            ->from($entityName, $alias);

        $filters = $config->offsetGetByPath('[source][query_config][filters]');

        $this->restrictionBuilder->buildRestrictions($filters, $ds);

        return $qb;
    }
}
