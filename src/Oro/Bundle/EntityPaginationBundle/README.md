OroEntityPaginationBundle
=========================

This bundle provides ability to navigate over grid entities from an entity view and entity edit pages.


How to enable pagination
------------------------

To enable entity pagination you have to add option ``entity_pagination`` to a datagrid options. If this option
is enabled then session collects identifiers of entities at the first visit view or edit page of any entity from
specified grid, and these identifiers are used to generate links to a previous and next entities on this page.

Also datagrid must have column with the name same to entity identifier field that is used to collect identifiers. 
View and edit pages routes must have parameter with the same name.

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

When user comes from grid with enabled entity pagination to view or edit page, grid parameters (filters, sorters etc.)
transmitted as url parameters in the browser address bar. Then entity pagination storage data collector send a query for
get all records with this grid parameters considering ACL permissions (for example edit ACL permissions are more strict
than view). There are two different scopes in storage for collect data. One scope for collect view  pagination entities
identifiers and other scope for collect edit pagination entities identifiers.
Then collector fill view or edit scope depending on which page user visit. If limit of records count is more than
**Entity Pagination limit** collector set empty array for this scope. Next time, if storage has data for current scope
for current grid parameters, collector will not be send request for get records. 
When switching back to datagrid both storage's scopes will be cleared.
There are ``EntityPaginationController`` actions for entity pagination navigation. Each action check is pagination
identifier is available and accessible. If in time when storage has data for current scope, other user delete some
entities which are in current scope. When user go by navigation to this entity the message ``not_available`` will
be show and user see next available entity. If was changed ACL permissions for entities which are in current scope
and user go by navigation to this entity the message ``not_accessible`` will be show. Not available or not accessible
entity delete from storage, change entities identifier count and message ``stats_number_view_%count% `` will be show.

Default entity view has placeholder used to add entity pagination section. When user came to entity view page this
section shows pagination details (<M> of <N> entities, links to first, previous, next and last entities)
extracted from user session for current entity type.
