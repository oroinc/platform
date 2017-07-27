Implementation
========

Currently, the application has two types of ACL extensions: Actions(Capabilities) and Entities.

**Entity**

Resources, that gives control on entity manipulations (View, Edit, Delete etc.).

To mark an entity as ACL protected, the next config to the @Configurable annotation in entity class should be added:

``` php
/**
...
* @Config(
*  defaultValues={
    ...
*      "security"={
*          "type"="ACL",
           "permissions"="All"
*          "group_name"="SomeGroup"
*          "category"="SomeCategory"
*      }
    ...
*  }
* )
...
 */
 class MyEntity
```
**NOTE:** after changing ACL in Config annotation you should run oro:entity-config:update command in console to apply changes

**permissions** parameter is used to specify the access list for the entity. This parameter is optional.
If it is not specified, or is "All", it is considered that the entity access to all available security permissions.

You can create your list of accesses. For example, the string "VIEW;EDIT" will set the permissions parameters for the entity for viewing and editing.

**group_name** parameter is used to group entities by applications. It is used to split security into application scopes. 

**category** parameter is used to categorise entity. It is used to split entities by section on the role privileges edit page.

You can use @Acl and @AclAncestor annotations to protect controller actions.

 - Using @Acl annotation:

``` php
use Oro\Bundle\SecurityBundle\Annotation\Acl; #required for Acl annotation
...
/**
 * @Acl(
 *      id="myentity_view",
 *      type="entity",
 *      class="MyBundle:MyEntity",
 *      permission="VIEW"
 * )
 */
public function viewAction()
```
This means that the view action is executable if VIEW premission is granted to MyEntity

 - Using acls.yml file from MyBundle/Resource/config/oro/acls.yml:

``` yml
acls:
    myentity_view:
        type: entity
        class: MyBundle:MyEntity
        permission="VIEW"
```
Than it can be used in @AclAncestor annotation
``` php
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor; #required for AclAncestor annotation
...
/**
 * @AclAncestor("myentity_view")
 */
public function viewAction()
```

or check in code directly with [Authorization Checker service](#checkAccess)

``` php
$this->authorizationChecker->isGranted('myentity_view')
```

 **Capabilities**:

Additional resources that are not related to an entity, e.g. Configuration, Search etc.

There are 2 ways to declare capability permissions:

 - Using @Acl annotation:

``` php
use Oro\Bundle\SecurityBundle\Annotation\Acl; #required for Acl annotation
...
/**
* @Acl(
*      id="can_do_something",
*      type="action",
*      label="Do something",
*      group_name="Some Group"
*      category="SomeCategory"
* )
*/
public function somethingAction()
```

 - Using acls.yml file from MyBundle/Resource/config/oro/acls.yml:

``` yml
acls:
    can_do_something:
        label: Do something
        type: action
        group_name: "Some Group"
        category: "SomeCategory"
        bindings: ~
```

Than it can be used in @AclAncestor annotation
``` php
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor; #required for AclAncestor annotation
...
/**
 * @AclAncestor("can_do_something")
 */
public function somethingAction()
```

or check in code directly with [Authorization Checker service](#checkAccess)

``` php
$this->authorizationChecker->isGranted('can_do_something')
```

If you'd like to bind acl resource to specific controller action, you can use bindings:

``` yml
can_do_something_specific:
    label: Do something
    type: action
    group_name: "Some Group"
    category: "SomeCategory"
    bindings:
        - {  class: someClass, method: someMethod}
```

In this case, when someMethod of someClass is called, can_do_something_specific premission will be checked.

#### Check Access

The `security.authorization_checker` is a public service that is used to check whether an access to a resounce is granted or denied. This service represents the [Authorization Checker](https://symfony.com/doc/current/components/security/authorization.html#authorization-checker). The implementation of the Platform specific attributes and objects is in [AuthorizationChecker](../../Authorization/AuthorizationChecker.php) class.

The main entry point is `isGranted` method:

``` php
isGranted($attributes[, $object])
```

**$attributes** can be a role name(s), permission name(s), an ACL annotation id, a string in format "permission;descriptor" (e.g. "VIEW;entity:AcmeDemoBundle:AcmeEntity" or "EDIT;action:acme_action") or some other identifiers depending on registered security voters.

**$object** can be an entity type descriptor (e.g. "entity:Acme/DemoBundle/Entity/AcmeEntity" or  "action:some_action"), an entity object, instance of `ObjectIdentity`, `DomainObjectReference` or `DomainObjectWrapper`

**Examples**

Checking access to some ACL annotation resource

``` php
$this->authorizationChecker->isGranted('some_resource_id')
```
Checking VIEW access to the entity by class name

``` php
$this->authorizationChecker->isGranted('VIEW', 'Entity:MyBundle:MyEntity' );
```

Checking VIEW access to the entity's field

``` php
$this->authorizationChecker->isGranted('VIEW', new FieldVote($entity, $fieldName) );
```

Checking ASSIGN access to the entity object

``` php
$this->authorizationChecker->isGranted('ASSIGN', $myEntity);
```

Checking access is performed in the following way: **Object-Scope**->**Class-Scope**->**Default Permissions**.

For example, we are checking View permission to $myEntity object of MyEntity class. When we call

``` php
$this->authorizationChecker->isGranted('VIEW', $myEntity);
```
first ACL for `$myEntity` object is checked, if nothing is found, then it checks ACL for `MyEntity` class and if no records are found, finally checks the Default(root) permissions.

Also there are two additional authorization checkers that may be helpful is some cases:

- [ClassAuthorizationChecker](../../Authorization/ClassAuthorizationChecker.php)
- [RequestAuthorizationChecker](../../Authorization/RequestAuthorizationChecker.php)
