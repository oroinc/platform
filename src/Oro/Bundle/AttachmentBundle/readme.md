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
