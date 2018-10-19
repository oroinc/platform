<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SystemEmailTemplateSelectType extends AbstractType
{
    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->em  = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'query_builder' => $this->getRepository()->getSystemTemplatesQueryBuilder(),
            'class' => 'OroEmailBundle:EmailTemplate',
            'choice_label' => 'name',
            'choice_value' => 'name',
        ]);
    }

    /**
     * {@inheritdoc}
     */
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
        return 'oro_email_system_template_list';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return Select2TranslatableEntityType::class;
    }

    /**
     * @return EmailTemplateRepository
     */
    protected function getRepository()
    {
        return $this->em->getRepository('OroEmailBundle:EmailTemplate');
    }
}
