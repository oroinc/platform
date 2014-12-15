<?php

namespace Oro\Bundle\CommentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CommentBundle\Model\ExtendComment;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_comment")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="comments_type")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-comments"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "activity"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class Comment extends ExtendComment
{

}
