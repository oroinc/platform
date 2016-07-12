Field ACL
=========

Field ACL allow to check permission on specific field of entity.

Field ACL supports next permissions: VIEW, CREATE, EDIT. 

By default, Field ACL is disabled. To turn the ability to check fields for some entity, it should have `field_acl_supported` parameter of entity config
in `security` scope for this entity:

```

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
 
 If you have existing entity, additionally new migration should be created that will set `field_acl_supported` property for entity:
 
 
```
 
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

After that, in entity config page of this entity will be two additional parameters: `Field Level ACL` and `Show Restricted`.

WIth `Field Level ACL` parameter, system manager will be able to turn on Field ACL for given entity. 

With `Show Restricted` parameter, if Field ACL enabled and user have no access to field, on create and edit pages this field will be shown as disabled field.

Check Field ACL in php code
---------------------------

You can check access to some field with `oro_security.security_facade` service. To do this, you should create `FieldVote` instance and set is as the second parameter of isGranted method:


```
<?php
....
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\Security\Acl\Voter\FieldVote;
...
 
$isGranted = $this->securityFacade->isGranted('VIEW', new FieldVote($entity, 'fieldName'));

```

As result, $isGranted variable will contain true value if access is granted and false otherwise.

$entity parameter should contain an instance of entity you want to check.

In case if you does not have entity instance but have the class name, id of record, owners and organization ids of this record, you can use  object of `EntityObjectReference` class:
 
```
<?php
....
use Oro\Bundle\SecurityBundle\Acl\Domain\EntityObjectReference;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\Security\Acl\Voter\FieldVote;
...

$entityReference = new EntityObjectReference($entityClassName, $entityId, $ownerId, $organizationId);
$isGranted = $this->securityFacade->isGranted('VIEW', new FieldVote($entityReference, 'fieldName'));

``` 

Check Field ACL in twig templates
---------------------------------

In twig templates you can use `resource_granted` twig function with the field name as the third parameter:
 
```
{% if  resource_granted('VIEW', entity, 'fieldName') %}
    {# do some job #}
{% endif %}
```
