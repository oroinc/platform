<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Oro\Bundle\ApiBundle\Autocomplete\OpenApiSpecificationEntity;
use Oro\Bundle\ApiBundle\Autocomplete\OpenApiSpecificationEntityProviderInterface;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for select entities for which the OpenAPI specification should be created.
 */
class OpenApiSpecificationEntitiesSelectType extends AbstractType
{
    private OpenApiSpecificationEntityProviderInterface $entityProvider;

    public function __construct(OpenApiSpecificationEntityProviderInterface $entityProvider)
    {
        $this->entityProvider = $entityProvider;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['view_field']);
        $resolver->setDefaults([
            'autocomplete_alias' => 'openapi_specification_entities',
            'configs'            => [
                'multiple' => true,
                'per_page' => 30
            ],
            'transformer'        => new CallbackTransformer(
                static function ($data) {
                    return $data;
                },
                static function ($data) {
                    return $data;
                }
            )
        ]);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['configs']['component'] = 'autocomplete-openapi-specification-entities';
        $view->vars['component_options']['viewSelector'] = '#' . $view->parent[$options['view_field']]->vars['id'];
        $this->updateSelectedData($view, $options);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_openapi_specification_entities_select';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroJquerySelect2HiddenType::class;
    }

    private function updateSelectedData(FormView $view, array $options): void
    {
        $encodedSelectedData = $view->vars['attr']['data-selected-data'] ?? null;
        if (!$encodedSelectedData) {
            return;
        }

        $entities = $this->getEntities($view->parent[$options['view_field']]->vars['value']);
        $updatedSelectedData = [];
        $selectedData = json_decode($encodedSelectedData, true, 3, JSON_THROW_ON_ERROR);
        foreach ($selectedData as $item) {
            $entity = $entities[$item['id']] ?? null;
            if (null !== $entity) {
                $updatedSelectedData[] = ['id' => $entity->getId(), 'name' => $entity->getName()];
            }
        }
        $view->vars['attr']['data-selected-data'] = json_encode($updatedSelectedData, JSON_THROW_ON_ERROR);
    }

    /**
     * @param string|null $view
     *
     * @return OpenApiSpecificationEntity[] [id => entity, ...]
     */
    private function getEntities(?string $view): array
    {
        if (!$view) {
            return [];
        }

        $result = [];
        $entities = $this->entityProvider->getEntities($view);
        foreach ($entities as $entity) {
            $result[$entity->getId()] = $entity;
        }

        return $result;
    }
}
