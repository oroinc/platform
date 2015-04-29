UPGRADE FROM 1.7 to 1.8
=======================

####OroIntegrationBundle:
- Integration import processors may be tagged with `oro_integration.sync_processor` DIC tag with `integration` name. During integration import appropriate processor will be used if registered.

####ImportExportBundle
 - `Oro\Bundle\ImportExportBundle\Context\ContextInterface` added $incrementBy integer parameter for methods: incrementReadCount, incrementAddCount, incrementUpdateCount, incrementReplaceCount, incrementDeleteCount, incrementErrorEntriesCount
