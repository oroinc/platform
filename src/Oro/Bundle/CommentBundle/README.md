OroCommentBundle
===================

The `OroCommentBundle` provide ability to comment activity entities. The system administrator can manage this functionality on *System / Entities / Entity Management* page.

How to enable comment association with new activity entity using migrations
---------------------------------------------------------------------------

Usually you do not need to provide predefined set of associations between the comment entity and activity entities, rather it is the administrator chose to do this. But it is possible to create this type of association using migrations if you need. The following example shows how it can be done:

``` php
<?php

namespace Oro\Bundle\CommentBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtension;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCommentBundle implements Migration, CommentExtensionAwareInterface
{
    /** @var CommentExtension */
    protected $comment;

    /**
     * @param CommentExtension $commentExtension
     */
    public function setCommentExtension(CommentExtension $commentExtension)
    {
        $this->comment = $commentExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addCommentToCalendarEvent($schema, $this->comment);
    }

    /**
     * @param Schema           $schema
     * @param CommentExtension $commentExtension
     */
    public static function addCommentToCalendarEvent(Schema $schema, CommentExtension $commentExtension)
    {
        $commentExtension->addCommentAssociation($schema, 'oro_calendar_event');
    }
}
```

How to enable comments on new activity entity list widget
---------------------------------------------------------

If you created the new activity entity and want to comment it in activity list widget you need implement CommentProviderInterface. An example:

```
class CalendarEventActivityListProvider implements ActivityListProviderInterface, CommentProviderInterface
{
...
/**
 * {@inheritdoc}
 */
public function hasComments(ConfigManager $configManager, $entity)
{
    $config = $configManager->getProvider('comment')->getConfig($entity);
    return $config->is('enabled');
}
...
```
