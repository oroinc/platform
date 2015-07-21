<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\FormBundle\Form\Type\OroEntityCreateOrSelectChoiceType;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class AutoResponseRuleType extends AbstractType
{
    const NAME = 'oro_email_autoresponserule';

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('active', 'checkbox', [
                'label' => 'oro.email.autoresponserule.active.label',
            ])
            ->add('name', 'text', [
                'label' => 'oro.email.autoresponserule.name.label',
            ])
            ->add('conditions', 'oro_collection', [
                'label' => 'oro.email.autoresponserule.conditions.label',
                'type' => AutoResponseRuleConditionType::NAME,
                'handle_primary' => false,
                'allow_add_after' => true,
            ])
            ->add('template', OroEntityCreateOrSelectChoiceType::NAME, [
                'label' => 'oro.email.autoresponserule.template.label',
                'class' => 'Oro\Bundle\EmailBundle\Entity\EmailTemplate',
                'create_entity_form_type' => 'oro_email_autoresponse_template',
                'select_entity_form_type' => 'oro_email_autoresponse_template_choice',
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            if (null === $rule = $event->getData()) {
                return;
            }

            if (null === $template = $rule->getTemplate()) {
                return;
            }

            $templateForm = $event->getForm()->get('template');
            $existingEntityForm = $templateForm->get('existing_entity');
            $templateForm->remove('existing_entity');

            $options = $existingEntityForm->getConfig()->getOptions();
            unset($options['choices']);
            unset($options['choice_list']);

            $templateForm->add('existing_entity', 'oro_email_autoresponse_template_choice', array_merge(
                $options,
                [
                    'choices' => null,
                    'query_builder' => function (EmailTemplateRepository $repository) use ($template) {
                        $qb = $repository->createQueryBuilder('e');

                        return $qb
                            ->orderBy('e.name', 'ASC')
                            ->andWhere('e.entityName = :entityName')
                            ->andWhere("e.organization = :organization")
                            ->andWhere($qb->expr()->orX(
                                $qb->expr()->eq('e.visible', ':visible'),
                                $qb->expr()->eq('e.id', ':id')
                            ))
                            ->setParameter('entityName', Email::ENTITY_CLASS)
                            ->setParameter('organization', $this->securityFacade->getOrganization())
                            ->setParameter('id', $template->getId())
                            ->setParameter('visible', true);
                    },
                ]
            ));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\EmailBundle\Entity\AutoResponseRule',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
