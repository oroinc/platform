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
    const NAME = 'oro_email_autoresponse_template_choice';

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(TokenAccessorInterface $tokenAccessor, TranslatorInterface $translator)
    {
        $this->tokenAccessor = $tokenAccessor;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'selectedEntity' => Email::ENTITY_CLASS,
            'query_builder' => function (EmailTemplateRepository $repository) {
                return $repository->getEntityTemplatesQueryBuilder(
                    Email::ENTITY_CLASS,
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
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /* @var $choice ChoiceView */
        foreach ($view->vars['choices'] as $choice) {
            /* @var $template EmailTemplate */
            $template = $choice->data;
            if (!$template->isVisible()) {
                $choice->label = $this->translator->trans('oro.form.custom_value');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EmailTemplateSelectType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
