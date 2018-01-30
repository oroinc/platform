## Entity Manager

In order to extend some native Doctrine Entity Manager functionality a new class [OroEntityManager](../../ORM/OroEntityManager.php) was implemented.
In case any other modification are required, your class should extend `OroEntityManager` instead of Doctrine Entity Manager.

**Additional ORM Lifecycle Events**

In addition to standard [Doctrine ORM Lifecycle Events](http://doctrine-orm.readthedocs.org/en/latest/reference/events.html#lifecycle-events), the `OroEntityManager` triggers new events:

- *preClose* - The preClose event occurs when the EntityManager#close() operation is invoked, before EntityManager#clear() is invoked.
