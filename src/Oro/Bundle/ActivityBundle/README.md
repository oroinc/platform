# OroActivityBundle

OroActivityBundle helps classify certain entity types as activities and enables a special relation type between an entity and activities. Activity relations can be managed in the entity management UI and in the DB migration files.

## How to Enable Activity Association Using Migrations

Although usually it is an administrator who provides a predefined set of associations between the activity entity and other entities, you can also create this type of associations using migrations, if necessary. 

The following example shows how this can be done:

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

## How to Make an Entity an Activity

To create an activity out of your new entity, you need to make the entity extended and include in the `activity` group. 

To make the entity extended, create a base abstract class. The name of this class should start with the `Extend` word,  and implement [ActivityInterface](./Model/ActivityInterface.php). 

Here is an example:

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

Use this class as the superclass for your entity. To include the entity in the `activity` group, you can use the ORO entity configuration, for example:

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

Your entity is now recognized as the activity entity. To make sure that the activity is displayed correctly, you need configure its UI.

## How to Configure UI for the Activity Entity


Before the new activity entity can be used in OroPlatform, you need to configure two things for entities that this activity can be assigned to:

 - [The activity list section](#how-to-configure-ui-for-activity-list-section)
 - [The add activity button](#how-to-configure-ui-for-activity-button)

Take a look at [all configuration options](./Resources/config/oro/entity_config.yml) for the activity scope before you continue reading.

### How to Configure UI for Activity List Section

First, create a new action in your controller and TWIG template responsible for rendering the list of your activities.

Keep in mind that:

 - The controller action must accept two parameters: `$entityClass` and `$entityId`.
 - The entity class name can be encoded to avoid routing collisions. That is why you need to use the `oro_entity.routing_helper` service to get the entity by its class name and id.
 - In the following example, the `activity-email-grid` datagrid is used to render the list of activities. This grid is defined in the *datagrids.yml* file:

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

To add an activity button on the view page of an entity that your activity can be assigned to, you need to do the following:

- Create two TWIG templates responsible for rendering the button and the link in the dropdown menu. Please note that you should provide both templates because an action can be rendered either as a button or a link depending on a number of actions, UI theme, device (desktop/mobile), etc.
 
 Here is an example of TWIG templates:

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
    'iCss': 'fa-envelope',
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
    'iCss': 'fa-envelope',
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

- Register these templates in *placeholders.yml*, for example:

``` yml
placeholders:
    items:
        oro_send_email_button:
            template: OroEmailBundle:Email:activityButton.html.twig
            acl: oro_email_email_create
        oro_send_email_link:
            template: OroEmailBundle:Email:activityLink.html.twig
            acl: oro_email_email_create
```

- Bind the items declared in *placeholders.yml* to the activity entity using the `action_button_widget` and `action_link_widget` attributes.
 
 For example:

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

## How to Configure Custom Grid for Activity Context Dialog

If you want to define a context grid for an entity (e.g User) in the activity context dialog, add the `context` option in the entity class `@Config` annotation, e.g: 

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

This option is used to recognize the grid for the entity with a higher priority than the `default` option.
If these options (`context` or `default`) are not defined for an entity, the grid does not appear in the context dialog.


## How to Enable Contexts Column in Activity Entity Grids

For any activity entity grid you can include a column that includes all context entities.

Have a look at the following example of tasks configuration in *datagrids.yml*:

``` yml
datagrids:
    tasks-grid:
        # extension configuration
        options:
            contexts:
                enabled: true          # default `false`
                column_name: contexts  # optional, column identifier, default is `contexts`
                entity_name: ~         # optional, set the FQCN of the grid base entity if auto detection fails
```

This creates a column named `contexts` and tries to automatically detect the activity class name. If for some reason it fails, you can specify a FQCN in the `entity_name` option.

If you wish to configure the column, add a section with the name specified in the `column_name` option:

``` yml
datagrids:
    tasks-grid:
        # column configuration
        columns:
             contexts:                      # the column name defined in options
                label: oro.contexts.label   # optional, default `oro.activity.contexts.column.label`
                renderable: true            # optional, default `true`
                ...
```

The column type is `twig` (unchangeable), so you can also specify a `template`.

Default is [OroActivityBundle:Grid:Column/contexts.html.twig](./Resources/views/Grid/Column/contexts.html.twig)

``` twig
{% for item in value %}
    {% spaceless %}
        <span class="context-item">
            <span class="{{ item.icon }}"></span>
            {% if item.link %}
                <a href="{{ item.link }}" class="context-label">{{ item.title|trim }}</a>
            {% else %}
                <span class="context-label">{{ item.title|trim }}</span>
            {% endif %}
        </span>
    {% endspaceless %}
    {{- not loop.last ? ', ' }}
{% endfor %}

```
