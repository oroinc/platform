<?php
namespace Oro\Bundle\SecurityBundle\ChoiceList;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class AclProtectedQueryBuilderLoader extends ORMQueryBuilderLoader
{
    /**
     * @var \Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper
     */
    private $aclHelper;
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    public function __construct($queryBuilder, $manager = null, $class = null, AclHelper $aclHelper)
    {
        // If a query builder was passed, it must be a closure or QueryBuilder
        // instance
        if (!($queryBuilder instanceof QueryBuilder || $queryBuilder instanceof \Closure)) {
            throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder or \Closure');
        }

        if ($queryBuilder instanceof \Closure) {
            if (!$manager instanceof EntityManager) {
                throw new UnexpectedTypeException($manager, 'Doctrine\ORM\EntityManager');
            }

            $queryBuilder = $queryBuilder($manager->getRepository($class));

            if (!$queryBuilder instanceof QueryBuilder) {
                throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder');
            }
        }

        $this->queryBuilder = $queryBuilder;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntities()
    {
        $query = $this->aclHelper->apply($this->queryBuilder, 'VIEW');
        return $query->execute();
    }

}