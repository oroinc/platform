<?php

namespace Oro\Bundle\CommentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CommentBundle\Model\ExtendComment;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\CommentBundle\Entity\Repository\CommentRepository")
 * @ORM\Table(name="oro_comment")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-comments",
 *              "category"="Comment"
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
    const ENTITY_NAME = 'Oro\Bundle\CommentBundle\Entity\Comment';
}
