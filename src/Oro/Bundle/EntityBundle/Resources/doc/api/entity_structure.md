# Oro\Bundle\EntityBundle\Model\EntityStructure

## ACTIONS  

### get

Get action is restricted.

Entity structure contains information about configurable entities (their aliases, translated labels, fields, options, and routes).

{@inheritdoc}

### get_list

Retrieve a collection of entities structure items.

Entity structure contains information about configurable entities (their aliases, translated labels, fields, options, and routes).

## FIELDS

### label

Translated label of the entity.

### pluralLabel

Translated plural label of the entity.

### alias

Entity alias.

### pluralAlias

Entity plural alias.

### className

Entity class name.

### icon

Entity icon.

### fields

Array of entity fields. For every field provided: name, type, label (translated), type of relation (`oneToMany`, 
`manyToMany` and so on), options (for example `[auditable: true, configurable: false, exclude: false]`)

### options

Array of entity options (for example `[auditable: true, exclude: false]`).

### routes

Entity routes.
