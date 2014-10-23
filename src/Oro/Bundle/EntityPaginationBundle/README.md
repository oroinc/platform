OroEntityPaginationBundle
=========================

This bundle provides ability to navigate over grid entities from an entity view page.


How to enable pagination
------------------------

To enable entity pagination you have to add option ``entity_pagination`` to a datagrid options. If this option
is enabled then session collects identifiers of entities from specified grid, and these identifiers are used on
a view page of a root datagrid entity to generate links to a previous and next entities.

Also datagrid must have column with the name same to entity identifier field that is used to collect identifiers,
and view page route must have parameter with the same name.

**Example**

Let's assume that pagination must be enabled for the User entity with identifier column called "id".

1) Datagrid must have ``entity_pagination`` option in configuration:

```yml
datagrid:
    users-grid:
        ...
        options:
            entity_pagination: true
```

2) Datagrid has identifier column in result:

```yml
datagrid:
    users-grid:
        ...
        source:
            ...
            query:
                select:
                    - u.id
                    ...
        properties:
            id: ~
```

3) User view page route has identifier column in route parameters:

```php
class UserController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_user_view", requirements={"id"="\d+"})
     * ...
     */
    public function viewAction(User $user)
    {
        ...
    }

    ...
}
```


System Configuration
--------------------



