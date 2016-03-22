How to
======


Table of Contents
-----------------
 - [Turn on API for entity](#overview)
 - [Change ACL resource for entity actions](#change-acl-resource-for-entity-actions)
 - [Turn off entity action](#turn-off-entity-action)
 - [Change delete handler for entity](#change-delete-handler-for-entity)


Turn on API for entity
----------------------

By default, API for entities is disabled. To turn on API for some entity, You should add this entity to `Resources/config/oro/api.yml` of your bundle:


```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product: ~
```

Change ACL resource for entity actions
--------------------------------------

By default, the following permissions are used to restrict access to an entity in a scope of the specific action:

| Action | Permission |
| --- | --- |
| get | VIEW |
| get_list | VIEW |
| delete | DELETE |

In case if you want to change an action permission to another, or disable access checks, for some action, you can do it with `acl_resource` parameter from `actions` section. For example, lets's change it for `delete` and `get` actions and turn off access checks for `get_list` action.

For this you should implement an ACL resource with @ACL annotation in controller class, or with resource definition in `Resources/config/acl.yml` file of your bundle:

```yaml
access_entity_capability: #creates the capability with access_entity_capability name
    label: acme.demo.access_entity_capability
    type: action
    group_name: ""
    
access_entity_view: #creates the ACL resource for Product entity with VIEW permission
    type: entity
    class: AcmeDemoBundle:Product
    permission: VIEW
```

The next step is the change permissions at `Resources/config/oro/api.yml` of your bundle:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                get:
                    acl_resource: access_entity_capability
                get_list:
                    acl_resource: ~
                delete:
                    acl_resource: access_entity_view                     
```
 
Turn off entity action
----------------------

By default, then you add some entity to the API, all the actions will be available for this entity.

In case if some action should not be accessable, you can disable it. For example, let's disable `get` and `delete` actions for Product entity:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                get: false
                delete:
                    excluded: true                     
```

Change delete handler for entity
--------------------------------

By default, delete process handles by [DeleteHandler](../../../SoapBundle/Handler/DeleteHandler.php).

If your entity's delete process should be different, you can change default delete handler for your entity.

For example, lets's add some custom checks for Product entity.

To do this, first of all, you should create own delete handler. It should be extended from the standart [DeleteHandler](../../../SoapBundle/Handler/DeleteHandler.php):

```php
<?php

namespace Acme\DemoBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

class CustomProductDeleteHandler extends DeleteHandler
{

    /**
     * {@inheritdoc}
     */
    public function checkPermissions($entity, ObjectManager $em)
    {
        parent::checkPermissions($entity, $em);
        
        $deleteGranted = ... // here some custom checks for Product entity
        if (!deleteGranted) {
            throw new ForbiddenException('forbidden for some reason');
        }
    }
}                   
```

The next step is declare this handler as service:

```yaml
#services.yml
services:
    acme.demo.product_delete_handler:
        class: Acme\DemoBundle\Handler\CustomProductDeleteHandler              
```

And the final step is the change of `delete_handler` parameter at `Resources/config/oro/api.yml` of your bundle:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            delete_handler: acme.demo.product_delete_handler                  
```
