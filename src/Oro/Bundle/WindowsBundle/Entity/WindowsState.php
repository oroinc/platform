<?php

namespace Oro\Bundle\WindowsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Window state container Entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\WindowsBundle\Entity\Repository\WindowsStateRepository")
 * @ORM\Table(name="oro_windows_state",
 *      indexes={@ORM\Index(name="windows_user_idx", columns={"user_id"})})
 */
class WindowsState extends AbstractWindowsState
{
    /**
     * @var UserInterface $user
     *
     * @ORM\ManyToOne(targetEntity="Symfony\Component\Security\Core\User\UserInterface")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function setUser(UserInterface $user)
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
}
