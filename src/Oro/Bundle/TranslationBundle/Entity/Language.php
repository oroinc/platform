<?php

namespace Oro\Bundle\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;

/**
 * Store Language in a database
 *
 * @ORM\Table(name="oro_language")
 * @ORM\Entity(repositoryClass="Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-flag"
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id",
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class Language implements DatesAwareInterface, OrganizationAwareInterface
{
    use DatesAwareTrait;
    use OrganizationAwareTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=16, unique=true)
     */
    protected $code;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default"=false})
     */
    protected $enabled = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="installed_build_date", type="datetime", nullable=true)
     */
    protected $installedBuildDate;

    /**
     * @var bool
     *
     * @ORM\Column(name="local_files_language", type="boolean", options={"default"=false})
     */
    private $localFilesLanguage = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setCode(string $code): Language
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param \DateTime $installedBuildDate
     *
     * @return $this
     */
    public function setInstalledBuildDate(\DateTime $installedBuildDate = null)
    {
        $this->installedBuildDate = $installedBuildDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getInstalledBuildDate()
    {
        return $this->installedBuildDate;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;

        return $this;
    }

    public function isLocalFilesLanguage(): bool
    {
        return (bool) $this->localFilesLanguage;
    }

    public function setLocalFilesLanguage(bool $localFilesLanguage): Language
    {
        $this->localFilesLanguage = $localFilesLanguage;

        return $this;
    }
}
