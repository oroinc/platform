<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The form type for choice view for which the OpenAPI specification should be created.
 */
class OpenApiSpecificationViewChoiceType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choice_attr'          => function (string $choice) {
                return [
                    'data-description' => $choice ? $this->getViewDescription($choice) : ''
                ];
            },
            'translatable_options' => false
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_openapi_specification_view_choice';
    }

    private function getViewDescription(string $view): string
    {
        $key = sprintf('oro.api.open_api.views.%s.description', $view);
        $description = $this->translator->trans($key);
        if ($description === $key) {
            $description = '';
        }

        return $description;
    }
}
