# OroAttachmentBundle

OroAttachmentBundle introduces two entity field types: an image and a file, and enables their usage with the Oro extended entities.

## System Configuration

In the system configuration, under `General Setup > Upload settings`, a user can configure supported mime types for files and image fields.

Each mime type should be set from a new line.

User can set only available mime types from configuration.

To add or remove available mime types, add changes to the `upload_file_mime_types` section and `upload_image_mime_types` in the config.yml file:

```yml
oro_attachment:
    upload_file_mime_types:
        - application/msword
        - application/vnd.ms-excel
        - application/pdf
        - application/zip
        - image/gif
        - image/jpeg
        - image/png
    upload_image_mime_types:
        - image/gif
        - image/jpeg
        - image/png
```

## File Type

File type enables to upload files to any entity.

When creating a new file field type, a user should specify the maximum size of the file supported for this field.

On the entity record's details page, this field is displayed as a link to download this file.

## Image Type

Image file type enables to upload images to any entity.

When creating a new image field type, a user should specify maximum size of the file supported for this field as well as its width and height to enable the thumbnail image preview.

On the entity record's details page, this field is displayed as a thumbnail image with a link to download the original image file.

## Storage Configuration

OroAttachmentBundle uses [KnpGaufretteBundle](https://github.com/KnpLabs/KnpGaufretteBundle) to provide a filesystem abstraction layer.

Based on the default configuration, it stores files in `var/attachment directory` of your project. A user can reconfigure these settings. You can find more information on the KnpGaufretteBundle configuration in [documentation](https://github.com/KnpLabs/KnpGaufretteBundle/blob/master/README.markdown).

Image thumbnail files are created from [LiipImagineBundle](https://github.com/liip/LiipImagineBundle) and are stored in the `public/media/cache/attachment` directory.

## ACL Protection

Provides access to files and images of the entity which they are assigned to. A user should have view permissions to a parent record to be authorized to download attached files.

## Migration Extension Usage Example

It is possible to create an image or a file field via migrations using AttachmentExtension. For example:

```
<?php

namespace Acme\Bundle\DemoBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AcmeDemoBundle implements Migration, AttachmentExtensionAwareInterface
{
    /** @var AttachmentExtension */
    protected $attachmentExtension;

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            'entity_table_name', // entity table, e.g. oro_user, orocrm_contact etc.
            'new_field_name', // field name
            [], //additional options for relation
            7, // max allowed file size in megabytes, can be omitted, by default 1 Mb
            100, // thumbnail width in pixels, can be omitted, by default 32
            100 // thumbnail height in pixels, can be omitted, by default 32
        );
    }
}

```

Also, you can enable attachments for an entity, e.g.:

```
<?php

namespace Acme\Bundle\DemoBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;

class AcmeDemoBundle implements Migration, AttachmentExtensionAwareInterface
{
    /** @var AttachmentExtension */
    protected $attachmentExtension;

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->attachmentExtension->addAttachmentAssociation(
            $schema,
            'entity_table_name', // entity table, e.g. oro_user, orocrm_contact etc.
            [], // optional, allowed MIME types of attached files, if empty - global configuration will be used
            2 // optional, max allowed file size in megabytes, by default 1 Mb
        );
    }
}
```

# Entity Attachments

Configurable entities can use attachments for adding additional files to their records.

To enable attachments for an entity, an administrator should enable them in the current entity configuration.

Additionally, admin can set array with allowed mine types and maximum sizes of the attached files.

If no mime types were set, the mime types from `Upload settings` (system configuration) is used for validation.

Once the schema is updated, the `Add attachment` button becomes available for the current entity.

# Image Formatters

A user can use 3 formatters for image type fields.

`image_encoded` returns an image tag with embedded image content in the src attribute. Additional parameters:

- `alt` - a custom alt attribute for the image tag. By default, the original file name is used.

- `height` - a custom height attribute for the image tag. There is no default value for this attribute.

- `width`- custom width attribute for the image tag. There is no default value for this attribute.

`image_link` returns a link to the resized image (e.g. <a href='http://test.com/path/to/image.jpg'>image name</a>). Additional parameters:

- `title` - a custom image text value. By default, the original file name is used.

- `height` - a custom image height. By default, it is 100 px.

- `width`- a custom image width. By default, it is 100 px.

`image_src` returns the url to the resized image (e.g. http://test.com/path/to/image.jpg). Additional parameters:

- `height` - a custom image height. By default, it is 100 px.

- `width`- a custom image width. By default, it is 100 px.

# Debug Images Configuration

By default, images are processed by front controller (`index_dev.php`) in the `dev` environment. However, you can also enable your web server to process images instead of front controllers. It helps boost performance on all platforms and stability on Windows. 
To disable debug images, set the `debug_images` option to `false` in the config.yml file:

```yml
oro_attachment:
    debug_images: false
```
