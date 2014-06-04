#Reverse synchronization

There is possibility to push data back to channel. Export process could be used for this purposes.
Your connector should implement `Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface` to declare what's job name
will do export.

##Export job definition
This will export your data to your store based on channel definition.

**oro_integration.reader.entity.by_id** - service reads from entity by ID.

####Example:
``` yaml
    #batch_job.yml
    example_export:
        title: "Entity export"
        type:  export
        steps:
            export:
                title: export
                class: Oro\Bundle\BatchBundle\Step\ItemStep
                services:
                    reader:    oro_integration.reader.entity.by_id  # read entity from database by identifier
                    processor: YOUR_PROCESSOR                       # service which processing each record. Could prepare changeset for writer.
                    writer:    YOUR_REVERSE_WRITER                  # service that are responsible for data push to remote instance
                parameters: ~
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
