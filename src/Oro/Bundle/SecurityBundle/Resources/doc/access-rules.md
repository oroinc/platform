# Access Rules

Symfony's security system allows to check access to an existing object by the Authorization Checker's `isGranted` method. 

You can also check access to queries, such as Doctrine ORM query or Search query, for instance. 

## Protect ORM Queries

To protect ORM queries, an [AclHelper](../../ORM/Walker/AclHelper.php) was implemented. With its help, when you
call the `apply` method of the ACL helper with ORM Query Builder or ORM Query, the [AccessRuleWalker](../../ORM/Walker/AccessRuleWalker.php) is used
to process the query. This walker modifies the query's AST following the restrictions imposed by access rules.

An access rule is the class that implements the [AccessRuleInterface](../../AccessRule/AccessRuleInterface.php) interface. 

Each access rule modifies expressions of the [Criteria](../../AccessRule/Criteria.php) object.

The behaviour of access rules and AccessRuleWalker can be changed with additional options that can be set as third parameter of `apply` method.

The options that can change the behaviour of AccessRuleWalker:

- **checkRootEntity** --- Determined whether the root entity should be protected. Default value is `true`.
- **checkRelations** --- Determined whether entities associated with the root entity should be protected. Default value is `true`.

The options that can change the behaviour of access rules:

 - **aclDisable** --- Allows to disable the [AclAccessRule](../../AccessRule/AclAccessRule.php). Default value is `false`.
 - **aclCheckOwner** --- Enables checking of the current joined entity if it is the owner of a parent entity.
 - **aclParentClass** --- Contains the parent class name of the current joined entity. This option is used together with the check owner option.
 - **aclParentField** --- Contains the field name by which the current entity is joined. This option is used together with the check owner option.
 - **availableOwnerEnable** --- Enables the [AvailableOwnerAccessRule](../../AccessRule/AvailableOwnerAccessRule.php). Default value is `false`.
 - **availableOwnerTargetEntityClass**  --- The target class name whose access level should be used for the check in [AvailableOwnerAccessRule](../../AccessRule/AvailableOwnerAccessRule.php).
 - **availableOwnerCurrentOwner** --- The ID of owner that should be available even if ACL check denies access.

To find all possible options see classes that implement [AccessRuleInterface](../../AccessRule/AccessRuleInterface.php).

## Criteria Object

The criteria holds the necessary information about the type of query being checked, the object that should be checked, 
the access level, additional options and the expression that should be added to the query to limit access to the given object.

Additional criteria options represent a list of parameters that you can use to modify the behavior of an access rule.

## Criteria Expression
 
Each access rule can add expressions that should be applied to limit access to the given object.

Each Expression is a class that implements the [ExpressionInterface](../../AccessRule/Expr/ExpressionInterface.php) interface.

The following types of expressions are supported:

### CompositeExpression

[CompositeExpression](../../AccessRule/Expr/CompositeExpression.php) contains a list of expressions combined by the AND or OR operations.

### Comparison

[Comparison](../../AccessRule/Expr/Comparison.php) implements the comparison of expressions on the left and right by the given operator.

The following is a list of supported operators:

 - **=** - Equality;
 - **<>** - Inequality;
 - **<** - Less than;
 - **<=** - Less than or equal;
 - **\>** - Greater than;
 - **\>=** - Greater than or equal;
 - **IN** - Checks that left operand should contains in the list of right operand;
 - **NIN** - Checks that left operand should not contains in the list of right operand.
 
If the value of the expression on the left or right is not the expression object, it is converted to a *value expression*.

### NullComparison

[NullComparison](../../AccessRule/Expr/NullComparison.php) represents IS NULL or IS NOT NULL comparison expression.

### Path

[Path](../../AccessRule/Expr/Path.php) expression sets the path to the field. It is usually used as the left operand in the Comparison expression.

### Value

[Value](../../AccessRule/Expr/Value.php) expression holds the static value. It is usually used as the right operand in the Comparison expression.

### AccessDenied

[AccessDenied](../../AccessRule/Expr/AccessDenied.php) is an expression that denies access to an object.

### Exists

[Exists](../../AccessRule/Expr/Exists.php) is an expression that is used to test for the existence of any record in a subquery.

### Subquery

[Subquery](../../AccessRule/Expr/Subquery.php) is used to build a subquery.

## Add a New Access Rule

To add a new access rule, create a new class that implements [AccessRuleInterface](../../AccessRule/AccessRuleInterface.php), for example:

``` php
<?php

namespace Acme\DemoBundle\AccessRule;

use Acme\DemoBundle\Entity\Contact;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;

class ContactAccessRule implements AccessRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(Criteria $criteria): bool
    {
        return $criteria->getEntityClass() === Contact::class;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Criteria $criteria): void
    {
        $criteria->andExpression(new Comparison(new Path('source'), Comparison::EQ, 'call'));
    }
}
```

Next, the access rule class should be registered as a service with the `oro_security.access_rule` tag:

``` yaml
    acme_demo.access_rule.contact:
        class: Acme\DemoBundle\AccessRule\ContactAccessRule
        tags:
            - { name: oro_security.access_rule }
```

As a result, this access rule is applied to all ACL protected queries that have the *Contact* entity.

## Access Rules Visitor

If you want to apply access rule expressions to your type of queries, use a class that extends an abstract [Visitor](../../AccessRule/Visitor.php).

For example, [AstVisitor](../../ORM/Walker/AstVisitor.php) converts access rule expressions to Doctrine AST conditions.

Check [AccessRuleWalker](../../ORM/Walker/AccessRuleWalker.php) for details on how to use this visitor.
