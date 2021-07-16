<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * A class that checks whether access rules are applicable for a criteria object.
 * This matcher supports the following options:
 * * type - the type of a query, e.g. "ORM"
 * * permission - the ACL permission, e.g. "VIEW"
 * * entityClass - the FQCN of an entity
 * * loggedUserClass - the FQCN of a logged in user
 * * all options specific for a particular access rule
 */
class AccessRuleOptionMatcher implements AccessRuleOptionMatcherInterface
{
    private const OPTION_TYPE              = 'type';
    private const OPTION_PERMISSION        = 'permission';
    private const OPTION_ENTITY_CLASS      = 'entityClass';
    private const OPTION_LOGGED_USER_CLASS = 'loggedUserClass';

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var array|null */
    private $specialOptions;

    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function matches(Criteria $criteria, string $optionName, $optionValue): bool
    {
        if (null === $this->specialOptions) {
            $this->specialOptions = $this->getSpecialOptions();
        }

        if (isset($this->specialOptions[$optionName])) {
            return $this->specialOptions[$optionName]($criteria, $optionValue);
        }

        if (!$criteria->hasOption($optionName)) {
            return false === $optionValue;
        }

        return $criteria->getOption($optionName) === $optionValue;
    }

    /**
     * @return array [option name => callable, ...]
     */
    private function getSpecialOptions(): array
    {
        return [
            self::OPTION_TYPE              => function (Criteria $criteria, $optionValue) {
                return $criteria->getType() === $optionValue;
            },
            self::OPTION_PERMISSION        => function (Criteria $criteria, $optionValue) {
                return $criteria->getPermission() === $optionValue;
            },
            self::OPTION_ENTITY_CLASS      => function (Criteria $criteria, $optionValue) {
                return $criteria->getEntityClass() === $optionValue;
            },
            self::OPTION_LOGGED_USER_CLASS => function (Criteria $criteria, $optionValue) {
                return $this->tokenAccessor->getUser() instanceof $optionValue;
            }
        ];
    }
}
