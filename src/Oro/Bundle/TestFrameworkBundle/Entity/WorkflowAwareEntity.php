<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
 * Entity for testing workflows
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_workflow_aware_entity')]
#[Config(
    routeName: 'oro_test_wfa_index',
    routeView: 'oro_test_wfa_view',
    routeCreate: 'oro_test_wfa_create',
    routeUpdate: 'oro_test_wfa_update',
    routeDelete: 'oro_test_wfa_delete'
)]
class WorkflowAwareEntity implements TestFrameworkEntityInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, nullable: true)]
    protected ?string $name = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
