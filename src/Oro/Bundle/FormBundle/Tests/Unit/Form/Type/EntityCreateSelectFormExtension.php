<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntityType;
use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EntityCreateSelectFormExtension extends AbstractExtension
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    protected function loadTypes()
    {
        return [
            new TextType(),
            new TestEntityType(),
            new EntityIdentifierType($this->registry),
        ];
    }
}
