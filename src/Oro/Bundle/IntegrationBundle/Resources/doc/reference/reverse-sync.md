#Reverse synchronization

For integration that requires synchronization in both sides there is possibility to declare export process on connector level.
Your connector should implement `Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface` to expose job name
that will make export.

##Export job definition

Definition of the export job is very similar to import. Basically it's additional job for `Akeneo\Bundle\BatchBundle`
that should be added to `batch_job.yml`. Job might be declared with multiple steps, but good practice is to use one connector for one entity.
In order to read entity from database there is additional reader placed in OroIntegrationBundle `oro_integration.reader.entity.by_id`,
it takes `EntityReaderById::ID_FILTER` option from context object(`ContextInterface`) for matching entity to read.

_Note: for now only non-composite identifiers are supported_

####Example:
``` yaml
    #batch_job.yml
    example_export:
        title: Job title here
        type:  export
        steps:
            export_entity_1:
                title:      Step title here
                class:      Oro\Bundle\BatchBundle\Step\ItemStep
                services:
                    reader:    oro_integration.reader.entity.by_id  # read entity from database by identifier
                    processor: YOUR_PROCESSOR                       # service which process each record. Could prepare changeset for writer.
                    writer:    YOUR_REVERSE_WRITER                  # service that are responsible for pushing data to remote instance
                parameters: ~
            # .... another steps
```

Processor and writer could be initialized in your bundle in service.yaml

####Example:
``` yaml
    YOUR_PROCESSOR:
        class: %YOUR_PROCESSOR.class%
    YOUR_REVERSE_WRITER:
        class: %YOUR_REVERSE_WRITER.class%
```

Where `YOUR_PROCESSOR.class` - should implements Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface
and `YOUR_REVERSE_WRITER.class` - should implements Oro\Bundle\ImportExportBundle\Processor\WriterInterface

Implementation of those classes are very platform specific, so there isn't any abstraction layer.
