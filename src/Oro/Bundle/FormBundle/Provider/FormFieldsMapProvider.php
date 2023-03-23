<?php

namespace Oro\Bundle\FormBundle\Provider;

use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Provides a field map of all mapped form fields, including extended fields without non-scalar extended fields.
 */
class FormFieldsMapProvider
{
    private ManagerRegistry $managerRegistry;

    private array $scalarTypes = [
        Types::INTEGER,
        Types::SMALLINT,
        Types::BIGINT,
        Types::FLOAT,
        Types::DECIMAL,
        Types::BOOLEAN,
        Types::STRING,
        Types::TEXT,
        Types::ASCII_STRING,
    ];

    public function __construct(ManagerRegistry $entityManager)
    {
        $this->managerRegistry = $entityManager;
    }

    public function setScalarTypes(array $scalarTypes): void
    {
        $this->scalarTypes = $scalarTypes;
    }

    /**
     * @return array<string,array{key: string, name: string, id: string}>
     */
    public function getFormFieldsMap(FormView $view, FormInterface $form, array $options): array
    {
        $dataClass = $options['data_class'] ?? null;
        if (!$dataClass) {
            return [];
        }

        $entityManager = $this->managerRegistry->getManagerForClass($dataClass);
        if (!$entityManager) {
            return [];
        }

        $classMetadata = $entityManager->getClassMetadata($dataClass);

        $result = [];
        foreach ($view as $name => $childView) {
            $formFieldOptions = $form->get($name)->getConfig()->getOptions();

            if ($formFieldOptions['mapped']) {
                if (empty($formFieldOptions['is_dynamic_field'])
                    || in_array($classMetadata->getTypeOfField($name), $this->scalarTypes, true)) {
                    $result[$name] = [
                        'id' => $childView->vars['id'],
                        'key' => $name,
                        'name' => $childView->vars['full_name'],
                    ];
                }
            }
        }

        return $result;
    }
}
