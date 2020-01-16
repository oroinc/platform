Field ACL
=========

Field ACL allows checking access to an entity field and supports the following permissions: VIEW, CREATE, EDIT. 

Prepare the System for Field ACL
--------------------------------

By default, entity fields are not protected by ACL. The templates, datagrids and other parts of the system that use the entity
that should be Field ACL protected do not have such checks.

Before enabling the support of the Field ACL for an entity, prepare the system parts that use the entity to use Field ACL.

Check Field ACL in PHP Code
---------------------------

In PHP code, access to the field is provided by the `isGranted` method of the `security.authorization_checker` service.
The second parameter of this method should be an instance of [FieldVote](https://github.com/symfony/security-acl/blob/master/Voter/FieldVote.php): 

``` php
<?php
....
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Acl\Voter\FieldVote;
...
 
$isGranted = $this->authorizationChecker->isGranted('VIEW', new FieldVote($entity, 'fieldName'));

```

As a result, $isGranted variable contains the *true* value if access is granted and the *false* value if it does not.

$entity parameter should contain an instance of the entity that you want to check.

If you have no entity instance but you know a class name, ID of the record, the
owner and the organization IDs of this record, the [DomainObjectReference](../../Acl/Domain/DomainObjectReference.php) 
can be used as the domain object:
 
``` php
<?php
....
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Acl\Voter\FieldVote;
...

$entityReference = new DomainObjectReference($entityClassName, $entityId, $ownerId, $organizationId);
$isGranted = $this->authorizationChecker->isGranted('VIEW', new FieldVote($entityReference, 'fieldName'));

``` 

Check Field ACL in TWIG Templates
---------------------------------

Use the `is_granted` twig function to check grants in twig templates. 
To check the field, use the the field name as the third parameter of the function:
 
``` php
{% if is_granted('VIEW', entity, 'fieldName') %}
    {# do some job #}
{% endif %}
```

Enable Support of Field ACL for an Entity
-----------------------------------------

To be able to manage field ACL, add the `field_acl_supported` attribute to the 'security' scope of the entity config.
Enabling this attribute means that the system is prepared to check access to the entity fields.

You can achieve this with the Config annotation if you have access to both the entity and the process `oro:platform:update` command.
The following example is an illustration of the entity configuration:

``` php

<?php
....

 /**
 * @ORM\Entity()
 * @Config(
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "field_acl_supported"="true"
 *          },
 *      }
 * )
 */
 class SomeEntity extends ExtendSomeEntity
 {
 ...
 }
 
 ```
 
If you have no access to the entity to modify the Config annotation, set the `field_acl_supported` parameter with the migration:
 
``` php
 
<?php

namespace Acme\Bundle\DemoBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class TurnFieldAclSupportForEntity implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                'Acme\Bundle\DemoBundle\Entity\SomeEntity',
                'security',
                'field_acl_supported',
                true
            )
        );
    }
}

```

Enable Field ACL 
----------------

Once the configuration is changed, the entity config page has two additional parameters: `Field Level ACL` and `Show Restricted`.

**NOTE: Please do not enable these parameters from the code without enabling the `field_acl_supported` attribute for the entity.**

With the `Field Level ACL` parameter, the system manager can enable or disable Field ACL for the entity. 

When both *Show Restricted* and *Field ACL* options are enabled, but a user does not have access to the field, then
this field is displayed in a read-only format on the create and edit pages.

Limit Permissions List
----------------------

A developer can limit the list of available permissions for the field with the `permissions` parameter in the Security scope.

The permissions should be listed as the string with the `;` delimiter. 

For example:

``` php

<?php
....

 use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
...

 class SomeEntity extends ExtendSomeEntity
 {
 ...
     /**
      * @var string
      *
      * @ORM\Column()
      * @ConfigField(
      *      defaultValues={
      *          "security"={
      *              "permissions"="VIEW;CREATE",
      *          },
      *      }
      * )
      */
     protected $firstName;
 ...    
 }
 
 ```
