Oro Config Component
====================

`Oro Config Component` provides additional resource types to `Symfony Config Component` infrastructure responsibles for loading configurations from different data sources and optionally monitoring these data sources for changes.

Resource Types
--------------

 - [Cumulative Resources](./Resources/doc/cumulative_resources.md) provides a way to configure a bundle from other bundles.

Resource Merge
--------------

 - [Configuration Merger](./Resources/doc/configuration_merger.md) provides a way to merge configurations of some resource both from one or many bundles. Supports two strategies: replace and append.


System Aware Resolver
---------------------

The [System Aware Resolver](./Resources/doc/system_aware_resolver.md) allows to make your configuration files more dynamic. For example you can call service's methods, static methods, constants, context variables etc.
