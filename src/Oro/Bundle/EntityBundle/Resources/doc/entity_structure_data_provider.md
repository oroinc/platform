## Entity structure data provider ##

Namespace: `Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider`

Provides data of all configurable entities. Collects the following data (see `Oro\Bundle\EntityBundle\Model\EntityStructure`):
- Entity aliases
- Entity labels (translated)
- Entity Fields (see `Oro\Bundle\EntityBundle\Model\EntityFieldStructure`)
- Entity Options (for example `virtual`)
- Entity Routes.

For every field provided:
- name
- type
- label (translated)
- type of relation (`oneToMany`, `manyToMany` and so on)
- options (for example `configurable`).

This data can be returned by REST API (see 
`Oro\Bundle\EntityBundle\Controller\Api\Rest\EntityController::cgetStructureAction)`)

This data can be extended or modified using event (see [Entity Structure Options Event](./events.md#entity-structure-options-event))
