<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntityType;

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
            new TestEntityType(),
            new EntityIdentifierType($this->registry),
        );
    }
}
