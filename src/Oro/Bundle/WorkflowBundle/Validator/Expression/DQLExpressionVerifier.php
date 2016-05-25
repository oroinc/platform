<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Expression;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\QueryException;

use Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException;

class DQLExpressionVerifier implements ExpressionVerifierInterface
{
    /** @var EntityManagerInterface */
    private $registry;

    /** @var string */
    private $entityClass;

    /**
     * @param ManagerRegistry $registry
     * @param $entityClass
     */
    public function __construct(ManagerRegistry $registry, $entityClass)
    {
        $this->registry = $registry;
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function verify($expression)
    {
        try {
            /** @var EntityManagerInterface $manager */
            $manager = $this->registry->getManagerForClass($this->entityClass);

            $query = $manager->createQuery($expression);

            //Try to execute only "SELECT" queries, because they are safe
            if ($query->getAST() instanceof SelectStatement) {
                $query->setFirstResult(0)
                    ->setMaxResults(1)
                    ->execute();
            }

            return true;
        } catch (QueryException $e) {
            throw new ExpressionException($e->getMessage());
        }
    }
}
