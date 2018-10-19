## Gaufrette

The ImportExport bundle uses [Gaufrette](https://github.com/KnpLabs/Gaufrette) for the file storage. 
The gaufrette configuration is stored in `Resources/config/oro/app.yml`. 

## The Gaufrette configuration for the local filesystem

This configuration allows to use local filesystem for the importing and exporting. It is applicable if all the consumers run on the same server.

### Example
```
knp_gaufrette:
    adapters:
        importexport:
            local:
                directory: '%kernel.project_dir%/var/import_export'
    filesystems:
        importexport:
            adapter:    importexport
            alias:      importexport_filesystem
```

The importing, exporting, and temporary files are stored in the cache directory of the project.

## The gaufrette configuration for the Amazon S3 storage

This configuration allows to use Amazon S3 cloud service for the importing and exporting. It is applicable if consumers run on different servers.

### Example

```
services:
    aws_s3.client:
        class: AmazonS3
        arguments:
            -
                key: {your amazon s3 key}
                secret: {your amazon s3 secret}

knp_gaufrette:
    adapters:
        importexport:
            amazon_s3:
                amazon_s3_id: aws_s3.client
                bucket_name: {your bucket name}
                options:
                    directory: 'import_export'
                    create: true
                    region: {your amazon s3 bucket region}
```

## See also

[The official Gaufrette documentation](http://knplabs.github.io/Gaufrette/)
