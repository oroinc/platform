<?php

namespace Oro\Bundle\DraftBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

/**
 * Represents a Project of Drafts
 *
 * @ORM\Entity
 * @ORM\Table(name="oro_draft_project")
 * @Config(
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *          },
 *      }
 * )
 */
class DraftProject implements DatesAwareInterface, OrganizationAwareInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use UserAwareTrait;
    use ExtendEntityTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(string $title): DraftProject
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
