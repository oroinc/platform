<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository")
 * @ORM\Table(name="oro_navigation_menu_update")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-th"
 *          }
 *      }
 * )
 */
class MenuUpdate extends AbstractMenuUpdate
{
    const OWNERSHIP_BUSINESS_UNIT = 3;
    const OWNERSHIP_USER          = 4;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    protected $title;

    /**
     * {@inheritdoc}
     */
    public function getExtras()
    {
        $extras = [
            'title' => $this->getTitle()
        ];

        if ($this->getPriority() !== null) {
            $extras['position'] = $this->getPriority();
        }

        return $extras;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return MenuUpdate
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }
}
