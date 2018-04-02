## Entity structure data provider

Namespace: `Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider`

Provides data of all configurable entities. Collects the following data (see `Oro\Bundle\EntityBundle\Model\EntityStructure`):
- Entity aliases
- Entity labels (translated)
- Entity fields (see `Oro\Bundle\EntityBundle\Model\EntityFieldStructure`)
- Entity options (for example `auditable`)
- Entity routes.

For every field, the following information is provided:
- name
- type
- label (translated)
- type of relation (`oneToMany`, `manyToMany` and so on)
- options (for example `[configurable: true, auditable: false]`).

This data can be returned by [JSON API](./../config/oro/api.yml#L18)

This data can be extended or modified using event (see [Entity Structure Options Event](./events.md#entity-structure-options-event))
