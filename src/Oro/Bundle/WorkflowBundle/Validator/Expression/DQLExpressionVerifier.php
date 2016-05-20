<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Expression;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException;

class DQLExpressionVerifier implements ExpressionVerifierInterface
{
    /** @var EntityManagerInterface */
    private $em;

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
            $this->em->createQuery($expression)
                ->setFirstResult(0)
                ->setMaxResults(1)
                ->execute();
        } catch (\Exception $e) {
            throw new ExpressionException($e->getMessage());
        }
    }
}
