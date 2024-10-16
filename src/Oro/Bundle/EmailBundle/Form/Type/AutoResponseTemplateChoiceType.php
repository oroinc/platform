<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for Auto Response email templates selector.
 */
class AutoResponseTemplateChoiceType extends AbstractType
{
    private TokenAccessorInterface $tokenAccessor;
    private TranslatorInterface $translator;

    public function __construct(TokenAccessorInterface $tokenAccessor, TranslatorInterface $translator)
    {
        $this->tokenAccessor = $tokenAccessor;
        $this->translator = $translator;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'selectedEntity' => Email::class,
            'query_builder' => function (EmailTemplateRepository $repository) {
                return $repository->getEntityTemplatesQueryBuilder(
                    Email::class,
                    $this->tokenAccessor->getOrganization(),
                    true,
                    false
                );
            },
            'configs' => [
                'allowClear'  => true,
                'placeholder' => 'oro.form.custom_value',
            ]
        ]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /* @var ChoiceView $choice */
        foreach ($view->vars['choices'] as $choice) {
            /* @var EmailTemplate $template */
            $template = $choice->data;
            if (!$template->isVisible()) {
                $choice->label = $this->translator->trans('oro.form.custom_value');
            }
        }
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EmailTemplateSelectType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_email_autoresponse_template_choice';
    }
}
