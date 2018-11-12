## Implementation
[Base classes diagram](./diagrams/datagrid_base_uml.jpg) shows class relations and dependencies.

#### Key Classes

- Datagrid\Manager - responsible of preparing of grid and its configuration.
- Datagrid\Builder - responsible for creating and configuring the datagrid object and its datasource.
Contains registered datasource type and extensions, also it performs check for datasource availability according to ACL
- Datagrid\Datagrid - the main grid object, has knowledge ONLY about the datasource object and the interaction with it, all further modifications of the results and metadata come from the extensions
Extension\Acceptor - is a visitable mediator, contains all applied extensions and provokes visits at different points of the interactions.
- Extension\ExtensionVisitorInterface - visitor interface
- Extension\AbstractExtension - basic empty implementation
- Datasource\DatasourceInterface - link object between data and grid. Should provide results as array of ResultRecordInterface compatible objects
- Provider\SystemAwareResolver - resolves specific grid YAML syntax expressions. For more information, see the [references in configuration](./references_in_configuration.md) topic.
