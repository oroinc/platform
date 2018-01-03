# The Dependency Injection Tags

| Type name | Usage |
|-----------|-------|
| [oro_action.duplicate.filter_type](#oro_actionduplicatefilter_type) | Registers filter type for the Duplicator |
| [oro_action.duplicate.matcher_type](#oro_actionduplicatematcher_type) | Registers matcher type for the Duplicator |
| [oro.action.extension.doctrine_type_mapping](#oroactionextensiondoctrine_type_mapping) | Registers type mapping for guessing the Operation attribute types |
| [oro_action.operation_registry.filter](#oro_actionoperation_registryfilter) | Registers filter for the Operations collection |

## oro_action.duplicate.filter_type

Filter type for the [Duplicator](./actions.md#duplicate). 
For more information see the [DeepCopy](https://packagist.org/packages/myclabs/deep-copy) documentation.

## oro_action.duplicate.matcher_type

Matcher type for the [Duplicator](./actions.md#duplicate). 
For more information see the [DeepCopy](https://packagist.org/packages/myclabs/deep-copy) documentation.

## oro.action.extension.doctrine_type_mapping

Type mapping for guessing the Operation [attribute](./configuration-reference.md#attributes-configuration) type.

## oro_action.operation_registry.filter

Filter for operations to be disabled. Must implement [OperationRegistryFilterInterface](../../Model/OperationRegistryFilterInterface.php).
