<?php

namespace Oro\Bundle\DataGridBundle\Entity\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Entity\AppearanceType;

/**
 * Provides datagrid appearance types.
 */
class AppearanceTypeManager
{
    private ManagerRegistry $doctrine;
    private ?array $appearanceTypes = null;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Gets appearance types.
     *
     * @return array [name => ['label' => label, 'icon' => icon], ...]
     */
    public function getAppearanceTypes(): array
    {
        if (null === $this->appearanceTypes) {
            $appearanceTypes = [];
            $types = $this->doctrine->getRepository(AppearanceType::class)->findAll();
            /** @var AppearanceType $type */
            foreach ($types as $type) {
                $appearanceTypes[$type->getName()] = [
                    'label' => $type->getLabel(),
                    'icon'  => $type->getIcon()
                ];
            }
            $this->appearanceTypes = $appearanceTypes;
        }

        return $this->appearanceTypes;
    }
}
