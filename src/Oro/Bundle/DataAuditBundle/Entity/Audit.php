<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Audit model
 *
 * @ORM\Entity()
 * @Config(
 *      defaultValues={
 *          "security"={}
 *     }
 * )
 */
class Audit extends AbstractAudit
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $loggedAt
     *
     * @ORM\Column(name="logged_at", type="datetime", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "email"={
     *              "available_in_template"=false,
     *              "immutable"=true
     *          }
     *      }
     * )
     */
    protected $loggedAt;

    /**
     * @var string $objectId
     *
     * @ORM\Column(name="object_id", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "email"={
     *              "available_in_template"=false,
     *              "immutable"=true
     *          }
     *      }
     * )
     */
    protected $objectId;

    /**
     * @var string $objectClass
     *
     * @ORM\Column(name="object_class", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "email"={
     *              "available_in_template"=false,
     *              "immutable"=true
     *          }
     *      }
     * )
     */
    protected $objectClass;

    /**
     * @var string $objectName
     *
     * @ORM\Column(name="object_name", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "email"={
     *              "available_in_template"=false,
     *              "immutable"=true
     *          }
     *      }
     * )
     */
    protected $objectName;

    /**
     * @var integer $version
     *
     * @ORM\Column(type="integer", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "email"={
     *              "available_in_template"=false,
     *              "immutable"=true
     *          }
     *      }
     * )
     */
    protected $version;

    /**
     * @var string $username
     */
    protected $username;

    /**
     * @var AbstractUser[] $user
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "email"={
     *              "available_in_template"=false,
     *              "immutable"=true
     *          }
     *      }
     * )
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="owner_description", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "email"={
     *              "available_in_template"=false,
     *              "immutable"=true
     *          }
     *      }
     * )
     */
    protected $ownerDescription;

    /**
     * {@inheritdoc}
     */
    public function setUser(AbstractUser $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get user name
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getUser() ? $this->getUser()->getUsername() : '';
    }
}
