Field ACL
=========

Field ACL allows to check an access to an entity field.

Field ACL supports next permissions: VIEW, CREATE, EDIT. 

By default, entity fields are not protected by ACL. To be able to manage field ACL you should add the `field_acl_supported` attribute to 'security' scope of entity config.

If you need to allow manage ACL for field of your entity you can set `field_acl_supported` in entity config:

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
 
If you need to allow manage ACL for field of an entity for which you cannot modify @Config annotation you can set `field_acl_supported` with migration:
 
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

After that, in entity config page of this entity will be two additional parameters: `Field Level ACL` and `Show Restricted`.

WIth `Field Level ACL` parameter, system manager will be able to turn on Field ACL for given entity. 

When both Show Restricted and Field ACL options are enabled and an user does not have an access to a field this field will be read-only on create and edit pages.

Developer can limit the list of available permissions for the field. This can be done with `permissions` parameter in Security scope for the field.
The permissions should be listed as the string with `;` delimiter. 

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

Check Field ACL in php code
---------------------------

You can check access to some field with `oro_security.security_facade` service. To do this, you should create an instance of FieldVote class and pass it as the second parameter of isGranted method:


``` php
<?php
....
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\Security\Acl\Voter\FieldVote;
...
 
$isGranted = $this->securityFacade->isGranted('VIEW', new FieldVote($entity, 'fieldName'));

```

As result, $isGranted variable will contain true value if access is granted and false otherwise.

$entity parameter should contain an instance of entity you want to check.

In case if you does not have entity instance but have the class name, id of record, owner and organization ids of this record, you can use `EntityObjectReference` class:
 
``` php
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
 
``` php
{% if  resource_granted('VIEW', entity, 'fieldName') %}
    {# do some job #}
{% endif %}
```
