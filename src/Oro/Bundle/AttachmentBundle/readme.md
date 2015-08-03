##OroAttachmentBundle
===================

Manipulate attachments to entities

This bundle allows to add file and image field types to extend entities.

##System configuration.

In system configuration where is block `Upload settings`. In this block user can configure supported mime types for file and image field types.

Delimiter between each mime type is new line.

Additional you can set mime types templates. For example, mime record `image/*` will support all images file types.

##File type

File type allows to upload file to entity. 

By create new file field type process, user should specify maximum file size for this field.

In view page this field will be displayed as the link for download this file.

##Image type

Image file type allows to upload images to entities.

This field data will be shown as image thumb with link to download original image file.

By create new image field type process, user should specify maximum file size for this field and width and height of preview thumbnail of view page.

##Storage configuration

OroAttachmentBundle uses [KnpGaufretteBundle](https://github.com/KnpLabs/KnpGaufretteBundle) for providing a filesystem abstraction layer.

By default, it configured to store files in `app/attachment directory` of your project. User can reconfigure this settings. More info about KnpGaufretteBundle configuration can be found in [documentation](https://github.com/KnpLabs/KnpGaufretteBundle/blob/master/README.markdown).

Image thumbnails takes with [LiipImagineBundle](https://github.com/liip/LiipImagineBundle). Thumbnail files sores in `web/media/cache/attachment` directory.

##ACL protection

Access to files and images takes from entity, where this field types assigned. To have access to download attached file, user should have view permission to parent record.


##Migration Extension usage example

It is possible to create image or file field via migrations with help of AttachmentExtension. For example:

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

Also you can enable attachments for entity, e.g.:

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

#Entity attachments

Configurable entities can use attachments for adding additional files to records.

To turn attachments for entity, administrator should turn attachments in UI for current entity configuration.

Additional, admin can set array with allowed mine types and maximum attached file size. 
If mime types was not set, the mime types from `Upload settings` (system configuration) will be used for validation.

After the schema was updated, for current entity will be available button `Add attachment`.

#Image formatters

User can use 3 formatters for image type fields.

`image_encoded` returns img tag with embedded image content in src attribute. Additional parameters:

- `alt` - custom alt attribute for img tag. By default - original file name.

- `height` - custom height attribute for img tag. Has no default value.

- `width`- custom width attribute for img tag. Has no default value.

`image_link` returns link to the resized image (e.g. <a href='http://test.com/path/to/image.jpg'>image name</a>). Additional parameters:

- `title` - custom image text value. By default - original file name.

- `height` - custom image height. By default - 100 px.

- `width`- custom image width. By default - 100 px.

`image_src` returns url to the resized image (e.g. http://test.com/path/to/image.jpg). Additional parameters:

- `height` - custom image height. By default - 100 px.

- `width`- custom image width. By default - 100 px.