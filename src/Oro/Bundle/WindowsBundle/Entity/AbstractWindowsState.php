<?php

namespace Oro\Bundle\WindowsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareTrait;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Window state container Entity
 *
 * @ORM\MappedSuperclass
 */
abstract class AbstractWindowsState implements CreatedAtAwareInterface, UpdatedAtAwareInterface
{
    use CreatedAtAwareTrait;
    use UpdatedAtAwareTrait;

    /**
     * @var integer $id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $data
     *
     * @ORM\Column(name="data", type="json_array", nullable=false)
     */
    protected $data;

    /**
     * @var boolean
     */
    protected $renderedSuccessfully = false;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set data
     *
     * @param mixed $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns flag that window was rendered successfully
     *
     * @return bool
     */
    public function isRenderedSuccessfully()
    {
        return $this->renderedSuccessfully;
    }

    /**
     * Sets flag that window was rendered successfully
     *
     * @param bool $renderedSuccessfully
     */
    public function setRenderedSuccessfully($renderedSuccessfully)
    {
        $this->renderedSuccessfully = (bool)$renderedSuccessfully;
    }

    /**
     * Set user
     *
     * @param UserInterface $user
     * @return $this
     */
    abstract public function setUser(UserInterface $user);

    /**
     * Get user
     *
     * @return UserInterface
     */
    abstract public function getUser();
}
