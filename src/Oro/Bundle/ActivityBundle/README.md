OroActivityBundle
===================

The `OroActivityBundle` provide ability to assign activities (calls, emails, tasks) to other entities. The system administrator can manage this functionality on *System / Entities / Entity Management* page.

How to enable activity association using migrations
---------------------------------------------------

Usually you do not need to provide predefined set of associations between the activity entity and other entities, rather it is the administrator chose to do this. But it is possible to create this type of association using migrations if you need. The following example shows how it can be done:

``` php
<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;

class OroUserBundle implements Migration, ActivityExtensionAwareInterface
{
    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addActivityAssociations($schema, $this->activityExtension);
    }

    /**
     * Enables Email activity for User entity
     *
     * @param Schema            $schema
     * @param ActivityExtension $activityExtension
     */
    public static function addActivityAssociations(Schema $schema, ActivityExtension $activityExtension)
    {
        $activityExtension->addActivityAssociation($schema, 'oro_email', 'oro_user', true);
    }
}
```

How to make an entity as activity
---------------------------------

If you created the new entity and want to make it as the activity you need to make it the extended and include it in `activity` group. To make the entity extended you need create a base abstract class. The name of this class should start with `Extend` word and this class should implement [ActivityInterface](/Model/ActivityInterface.php). An example:

``` php
<?php

namespace Oro\Bundle\EmailBundle\Model;

use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Model\ExtendActivity;

class ExtendEmail implements ActivityInterface
{
    use ExtendActivity;

    /**
     * Constructor
     *
     * The real implementation of this method is auto generated.
     *
     * IMPORTANT: If the derived class has own constructor it must call parent constructor.
     */
    public function __construct()
    {
    }
}
```

And use this class as superclass for your entity. To include the entity in `activity` group you can use ORO entity configuration, for example:

``` php
/**
 *  @Config(
 *  defaultValues={
 *      "grouping"={"groups"={"activity"}}
 *  }
 * )
 */
class Email extends ExtendEmail
```

That's all. Now your entity will be recognized as the activity entity. But it is not enough to correct displaying your activity. The following section describes steps that should be done to configure UI of your activity.

How to configure UI for the activity entity
-------------------------------------------
Before the new activity entity can be used in ORO platform you need to configure two things for entities this activity can be assigned:

 - [The activity list section](#how-to-configure-ui-for-activity-list-section)
 - [The add activity button](#how-to-configure-ui-for-activity-button)

Also please take a look at [all configuration options](./Resources/config/entity_config.yml) for the activity scope before you continue reading.

### How to configure UI for activity list section

Let's start with the activity list. At the first you need to create the new action in your controller and TWIG template responsible to render the list of your activities.
Please pay attention that:

 - The controller action must accept two parameters: `$entityClass` and `$entityId`.
 - The entity class name can be encoded to avoid routing collisions. So you need to use `oro_entity.routing_helper` service to get the entity by it's class name and id.
 - In the following example the `activity-email-grid` datagrid is used to render the list of activities. This grid is defined in *datagrid.yml* file.

An example:

``` php
    /**
     * This action is used to render the list of emails associated with the given entity
     * on the view page of this entity
     *
     * @Route(
     *      "/activity/view/{entityClass}/{entityId}",
     *      name="oro_email_activity_view"
     * )
     *
     * @AclAncestor("oro_email_email_view")
     * @Template
     */
    public function activityAction($entityClass, $entityId)
    {
        return array(
            'entity' => $this->get('oro_entity.routing_helper')->getEntity($entityClass, $entityId)
        );
    }
```

``` twig
{% import 'OroDataGridBundle::macros.html.twig' as dataGrid %}

<div class="widget-content">
    {{ dataGrid.renderGrid('activity-email-grid', {
        entityClass: oro_class_name(entity, true),
        entityId: entity.id
    }) }}
</div>
```

Now you need to bind the controller to your activity entity. It can be done using ORO entity configuration, for example:

``` php
/**
 *  @Config(
 *  defaultValues={
 *      "grouping"={"groups"={"activity"}},
 *      "activity"={
 *          "route"="oro_email_activity_view",
 *          "acl"="oro_email_email_view"
 *      }
 *  }
 * )
 */
class Email extends ExtendEmail
```

Please note that in the above example we use `route` attribute to specify controller path and `acl` attribute to set ACL restrictions.

### How to configure UI for activity button

To add activity button on the view page of the entity your activity can be assigned, you need to do the following:

Create two TWIG templates responsible to render a button and a link in dropdown menu. Please note that you should provide both templates, because an action can be rendered as a button as a link and it can depends on a number of actions, UI theme, device (desktop/mobile) and so on. An example of TWIG templates:

activityButton.html.twig

``` twig
{{ UI.clientButton({
    'dataUrl': path(
        'oro_email_email_create', {
            to: oro_get_email(entity),
            entityClass: oro_class_name(entity, true),
            entityId: entity.id
    }) ,
    'aCss': 'no-hash',
    'iCss': 'icon-envelope',
    'dataId': entity.id,
    'label' : 'oro.email.send_email'|trans,
    'widget' : {
        'type' : 'dialog',
        'multiple' : true,
        'reload-grid-name' : 'activity-email-grid',
        'options' : {
            'alias': 'email-dialog',
            'dialogOptions' : {
                'title' : 'oro.email.send_email'|trans,
                'allowMaximize': true,
                'allowMinimize': true,
                'dblclick': 'maximize',
                'maximizedHeightDecreaseBy': 'minimize-bar',
                'width': 1000
            }
        }
    }
}) }}
```

activityLink.html.twig

``` twig
{{ UI.clientLink({
    'dataUrl': path(
        'oro_email_email_create', {
            to: oro_get_email(entity),
            entityClass: oro_class_name(entity, true),
            entityId: entity.id
    }),
    'aCss': 'no-hash',
    'iCss': 'icon-envelope',
    'dataId': entity.id,
    'label' : 'oro.email.send_email'|trans,
    'widget' : {
        'type' : 'dialog',
        'multiple' : true,
        'reload-grid-name' : 'activity-email-grid',
        'options' : {
            'alias': 'email-dialog',
            'dialogOptions' : {
                'title' : 'oro.email.send_email'|trans,
                'allowMaximize': true,
                'allowMinimize': true,
                'dblclick': 'maximize',
                'maximizedHeightDecreaseBy': 'minimize-bar',
                'width': 1000
            }
        }
    }
}) }}
```

Register these templates in *placeholders.yml*, for example:

``` yml
items:
    oro_send_email_button:
        template: OroEmailBundle:Email:activityButton.html.twig
        acl: oro_email_email_create
    oro_send_email_link:
        template: OroEmailBundle:Email:activityLink.html.twig
        acl: oro_email_email_create
```

Bind items declared in *placeholders.yml* to the activity entity using `action_button_widget` and `action_link_widget` attribute. For example:

``` php
/**
 *  @Config(
 *  defaultValues={
 *      "grouping"={"groups"={"activity"}},
 *      "activity"={
 *          "route"="oro_email_activity_view",
 *          "acl"="oro_email_email_view",
 *          "action_button_widget"="oro_send_email_button"
 *          "action_link_widget"="oro_send_email_link"
 *      }
 *  }
 * )
 */
class Email extends ExtendEmail
```

How to configure custom grid for activity context dialog
--------------------------------------------------------

If you want to define context grid for entity(e.g User) in the activity context dialog you need to add the
`context` option in entity class `@Config` annotation, e.g: 

``` php
/**
 * @Config(
 *      defaultValues={
 *          "grid"={
 *              default="default-grid",
 *              context="default-context-grid"
 *          }
 *     }
 * )
 */
class User extends ExtendUser
```

This option is used to recognize grid for entity with higher priority than `default` option.
In cases if these options (`context` or `default`) are not defined for entity, it won`t appear in the context dialog.
