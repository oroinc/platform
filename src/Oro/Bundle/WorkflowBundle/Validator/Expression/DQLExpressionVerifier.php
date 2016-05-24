<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Expression;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\QueryException;

use Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException;

class DQLExpressionVerifier implements ExpressionVerifierInterface
{
    /** @var EntityManagerInterface */
    private $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param mixed $expression
     *
     * @throws ExpressionException
     *
     * @return mixed
     */
    public function verify($expression)
    {
        try {
            $query = $this->em->createQuery($expression);

            //Try to execute only "SELECT" queries, because they are safe
            if ($query->getAST() instanceof SelectStatement) {
                $query->setFirstResult(0)
                    ->setMaxResults(1)
                    ->execute();
            }

            return $expression;
        } catch (QueryException $e) {
            throw new ExpressionException($e->getMessage());
        }
    }
}
