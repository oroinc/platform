<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Page state entity
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="oro_navigation_pagestate")
 */
class PageState extends AbstractPageState
{
    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;
}
