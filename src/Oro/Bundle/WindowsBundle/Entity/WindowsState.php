<?php

namespace Oro\Bundle\WindowsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\WindowsBundle\Entity\Repository\WindowsStateRepository;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Window state container Entity
 */
#[ORM\Entity(repositoryClass: WindowsStateRepository::class)]
#[ORM\Table(name: 'oro_windows_state')]
#[ORM\Index(columns: ['user_id'], name: 'windows_user_idx')]
class WindowsState extends AbstractWindowsState
{
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?UserInterface $user = null;

    #[\Override]
    public function setUser(UserInterface $user)
    {
        $this->user = $user;

        return $this;
    }

    #[\Override]
    public function getUser()
    {
        return $this->user;
    }
}
