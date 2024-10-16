<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Select email template from system templates.
 */
class SystemEmailTemplateSelectType extends AbstractType
{
    /**
     * @var ObjectManager
     */
    protected $em;

    public function __construct(ObjectManager $objectManager)
    {
        $this->em  = $objectManager;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'query_builder' => $this->getRepository()->getSystemTemplatesQueryBuilder(),
            'class' => EmailTemplate::class,
            'choice_label' => 'name',
            'choice_value' => 'name',
        ]);
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                function ($name) {
                    return $this->getRepository()->findByName($name);
                },
                function ($emailTemplate) {
                    if (is_null($emailTemplate)) {
                        return '';
                    }
                    return $emailTemplate->getName();
                }
            )
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_email_system_template_list';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2TranslatableEntityType::class;
    }

    /**
     * @return EmailTemplateRepository
     */
    protected function getRepository()
    {
        return $this->em->getRepository(EmailTemplate::class);
    }
}
