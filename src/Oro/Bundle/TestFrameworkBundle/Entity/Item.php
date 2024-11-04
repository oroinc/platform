<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Entity for testing search engine
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_search_item')]
#[Config(
    routeName: 'oro_test_item_index',
    routeView: 'oro_test_item_view',
    routeCreate: 'oro_test_item_create',
    routeUpdate: 'oro_test_item_update',
    defaultValues: [
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ]
    ]
)]
class Item implements TestFrameworkEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'stringValue', type: Types::STRING, nullable: true)]
    protected ?string $stringValue = null;

    #[ORM\Column(name: 'integerValue', type: Types::INTEGER, nullable: true)]
    protected ?int $integerValue = null;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'decimalValue', type: Types::DECIMAL, scale: 2, nullable: true)]
    protected $decimalValue;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'floatValue', type: Types::FLOAT, nullable: true)]
    protected $floatValue;

    #[ORM\Column(name: 'booleanValue', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $booleanValue = null;

    /**
     * @var string|resource|null
     */
    #[ORM\Column(name: 'blobValue', type: Types::BLOB, nullable: true)]
    protected $blobValue;

    /**
     * @var array
     */
    #[ORM\Column(name: 'arrayValue', type: Types::ARRAY, nullable: true)]
    protected $arrayValue;

    #[ORM\Column(name: 'datetimeValue', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $datetimeValue = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'guidValue', type: Types::GUID, nullable: true)]
    protected $guidValue;

    /**
     * @var object
     */
    #[ORM\Column(name: 'objectValue', type: Types::OBJECT, nullable: true)]
    protected $objectValue;

    /**
     * @var Collection<int, ItemValue>
     */
    #[ORM\OneToMany(mappedBy: 'entity', targetEntity: ItemValue::class, cascade: ['persist', 'remove'])]
    protected ?Collection $values = null;

    #[ORM\Column(name: 'phone1', type: Types::STRING, nullable: true)]
    protected ?string $phone = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }

    #[\Override]
    public function __toString()
    {
        return (string) $this->stringValue;
    }
}
