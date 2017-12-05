## Overview

# Table of Contents

 - [Main Components](#main-components)
 - [OroBatchBundle Configuration](#orobatchbundle-configuration)
 - [Supported Formats](#supported-formats)
 - [Dependencies](#dependencies)


## Main Components

### Job

OroImportExportBundle uses OroBatchBundle to organize the execution of the import/export operations.
OroBatchBundle implements a job which is configured with execution context and is run by a client.
The job is abstract by itself, it doesn't know specific details of what is going on during its execution.

### Step

Every job consists of steps, and each step aggregates three crucial components:
 * **Reader**
 * **Processor**
 * **Writer**

Each component is independent, with its own area of responsibility. First, a step uses the reader
to read the data from the source and gives it to the processor. Then, it obtains the processed results and give it to the writer.

### Reader

The reader reads the data from a source. In terms of import, it can be a CSV file with imported data. In terms of export, the source is a Doctrine entity, its repository, or another query builder.

### Processor

The processor is at the forefront of the job execution. The main logic of the specific job is concentrated here. The import processor converts array data to the entity object. The export processor does the opposite - converts the entity object to array representation.

### Writer

The writer is responsible for saving the results at a specific destination. In terms of import, it is a storage encapsulated with Doctrine. In terms of export, it is a plain CSV file.

### Serializer

The serializer namespace contains a dummy encoder (encoding/decoding is not needed for csv import), normalizers (collection, datetime, and entity), and required interfaces. It also contains the Serializer class extended from `Symfony\Component\Serializer\Serializer` to use both the extended `supportsDenormalization` and `supportsNormalization` methods.

### Strategy

The strategy namespace contains a strategy helper with generic import entities and ConfigurableAddOrReplaceStrategy that manages the entity import. StrategyInterface defines an interface for custom strategies.

### TemplateFixture

The TemplateFixture namespace contains a fixture functionality template. TemplateFixtureInterface is an interface used to create fixtures. TemplateManager is a storage for the template fixtures import.

## OroBatchBundle Configuration

This configuration is used by OroBatchBundle and encapsulates three jobs for importing the entity from a CSV file, validating the imported data and exporting the entity to a CSV file.

```
connector:
    name: oro_importexport
    jobs:
        entity_export_to_csv:
            title: "Entity Export to CSV"
            type: export
            steps:
                export:
                    title:     export
                    reader:    oro_importexport.reader.entity
                    processor: oro_importexport.processor.export_delegate
                    writer:    oro_importexport.writer.csv
        entity_import_validation_from_csv:
            title: "Entity Import Validation from CSV"
            type: import_validation
            steps:
                import_validation:
                    title:     import_validation
                    reader:    oro_importexport.reader.csv
                    processor: oro_importexport.processor.import_validation_delegate
                    writer:    oro_importexport.writer.doctrine_clear

        entity_import_from_csv:
            title: "Entity Import from CSV"
            type: import
            steps:
                import:
                    title:     import
                    reader:    oro_importexport.reader.csv
                    processor: oro_importexport.processor.import_delegate
                    writer:    oro_importexport.writer.entity
```

## Supported Formats

This bundle supports a CSV file format on the one hand and the Doctrine entity on the other hand.

## Dependencies

As was mentioned previously, OroBatchBundle is a major dependency of this bundle. OroBatchBundle is used to execute the import/export batch operations. But when a client bundle is using OroImportExportBundle, it doesn't depend directly on any classes, interfaces, or configuration files of OroBatchBundle. OroImportExportBundle provides its own interfaces and domain models for the client bundle to interact with. From the client bundle's perspective, it is not necessary to create any job configurations to support the import/export of an entity.
