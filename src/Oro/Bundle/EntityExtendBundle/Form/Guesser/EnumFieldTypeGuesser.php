<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Guesser;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Form\Guesser\AbstractFormGuesser;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsProviderInterface;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * Provides a guess for enum select form type based on enum scope config
 */
class EnumFieldTypeGuesser extends AbstractFormGuesser
{
    private const ENUM_FIELD_TYPE = 'enum';

    public function __construct(
        ManagerRegistry $managerRegistry,
        ConfigProvider $entityConfigProvider,
        protected ConfigProvider $formConfigProvider,
        private readonly ExtendFieldFormOptionsProviderInterface $enumFieldFormOptionsProvider,
    ) {
        parent::__construct($managerRegistry, $entityConfigProvider);
    }

    #[\Override]
    public function guessType(string $class, string $property): ?TypeGuess
    {
        $enumConfigProvider = $this->entityConfigProvider->getConfigManager()->getProvider('enum');
        if (!$enumConfigProvider || !$enumConfigProvider->hasConfig($class, $property)) {
            return null;
        }

        $enumFieldConfig = $enumConfigProvider->getConfig($class, $property);
        if (self::ENUM_FIELD_TYPE !== $enumFieldConfig->getId()->getFieldType()) {
            return null;
        }

        $enumCode = $enumFieldConfig->get('enum_code');
        if (!$enumCode) {
            return null;
        }

        $formType = EnumSelectType::class;
        $options = $this->enumFieldFormOptionsProvider->getOptions($class, $property);

        return $this->createTypeGuess($formType, $options);
    }
}
