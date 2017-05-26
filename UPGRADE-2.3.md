UPGRADE FROM 2.2 to 2.3
=======================

PhpUtils component
------------------
- Removed deprecated class `Oro\Component\PhpUtils\QueryUtil`. Use `Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil` instead

DoctrineUtils component
-----------------------
- Class `Oro\Component\DoctrineUtils\ORM\QueryUtils` was marked as deprecated. Its methods were moved to 4 classes:
    - `Oro\Component\DoctrineUtils\ORM\QueryUtil`
    - `Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil`
    - `Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil`
    - `Oro\Component\DoctrineUtils\ORM\DqlUtil`

MigrationBundle
---------------
- Added event `oro_migration.data_fixtures.pre_load` that is raised before data fixtures are loaded
- Added event `oro_migration.data_fixtures.post_load` that is raised after data fixtures are loaded

SearchBundle
------------
- Class `Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataListener` was replaced with `Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataFixturesListener`
- Service `oro_search.event_listener.reindex_demo_data` was replaced with `oro_search.migration.demo_data_fixtures_listener.reindex`

WorkflowBundle
--------------
- Class `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry`:
    - changed constructor signature:
        - added `Oro\Bundle\WorkflowBundle\Provider\WorkflowDefinitionProvider $definitionProvider`;
- Added provider `oro_workflow.provider.workflow_definition` to manage cached instances of `WorkflowDefinitions`.
- Added cache provider `oro_workflow.cache.provider.workflow_definition` to hold cached instances of `WorkflowDefinitions`.
