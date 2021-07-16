<?php

namespace Oro\Bundle\OrganizationBundle\Acl\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\NullComparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Denies access to entities that does not belong to the current organization.
 */
class OrganizationAwareAccessRule implements AccessRuleInterface
{
    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var string */
    private $organizationFieldName;

    /** @var bool */
    private $isOrganizationOptional = false;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        string $organizationFieldName = 'organization'
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->organizationFieldName = $organizationFieldName;
    }

    public function setOrganizationOptional(bool $isOrganizationOptional): void
    {
        $this->isOrganizationOptional = $isOrganizationOptional;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(Criteria $criteria): bool
    {
        return null !== $this->tokenAccessor->getOrganizationId();
    }

    /**
     * {@inheritdoc}
     */
    public function process(Criteria $criteria): void
    {
        $expr = new Comparison(
            new Path($this->organizationFieldName, $criteria->getAlias()),
            Comparison::EQ,
            $this->tokenAccessor->getOrganizationId()
        );
        if ($this->isOrganizationOptional) {
            $expr = new CompositeExpression(
                CompositeExpression::TYPE_OR,
                [$expr, new NullComparison(new Path($this->organizationFieldName, $criteria->getAlias()))]
            );
        }
        $criteria->andExpression($expr);
    }
}
