<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

class EntityCreateSelectFormExtension extends AbstractExtension
{
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    protected function loadTypes()
    {
        return array(
            new TextType(),
            new EntityType($this->registry),
            new EntityIdentifierType($this->registry),
        );
    }
}
