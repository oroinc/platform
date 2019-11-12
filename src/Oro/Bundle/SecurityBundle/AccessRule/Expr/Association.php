<?php

namespace Oro\Bundle\SecurityBundle\AccessRule\Expr;

use Oro\Bundle\SecurityBundle\AccessRule\Visitor;

/**
 * Checks access by access rules of associated entity.
 */
class Association implements ExpressionInterface
{
    /** @var string */
    private $associationName;

    /**
     * @param string $associationName
     */
    public function __construct(string $associationName)
    {
        $this->associationName = $associationName;
    }

    /**
     * @return string
     */
    public function getAssociationName(): string
    {
        return $this->associationName;
    }

    /**
     * {@inheritdoc}
     */
    public function visit(Visitor $visitor)
    {
        return $visitor->walkAssociation($this);
    }
}
