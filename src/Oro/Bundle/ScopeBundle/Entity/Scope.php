<?php

namespace Oro\Bundle\ScopeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\ScopeBundle\Model\ExtendScope;

/**
 * Class Scope
 * @Config()
 *
 * @ORM\Table("oro_scope")
 * @ORM\Entity(repositoryClass="Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository")
 */
class Scope extends ExtendScope
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
