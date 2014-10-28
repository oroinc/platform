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

Entity pagination has two system configuration options to handle pagination process. These options are accessible
in section "System configuration" > "General setup" > "Display settings" > "Data Grid settings".

* **Entity Pagination**, default is **true**, key _oro\_entity\_pagination.enabled_ - used to enable or disable
entity pagination all over the system
* **Entity Pagination limit**, default is **1000**, key _oro\_entity\_pagination.limit_ - allows to set maximum number
of entities in grid for entity pagination (i.e. if number of entities in grid more than limit then entity pagination
will not be available)


Backend processing
------------------

When user works with grid each request triggers listener that handles grid results. If entity pagination enabled both
in system configuration and in the grid, and number of entities in grid less than specified limit, then listener gets
IDs of all entities from grid. Then all these entities saved in user session for main grid entity.

Default entity view has placeholder used to add entity pagination section. When user came to entity view page this
section shows pagination details (<M> of <N> entities, links to first, previous, next and last entities)
extracted from user session for current entity type.
