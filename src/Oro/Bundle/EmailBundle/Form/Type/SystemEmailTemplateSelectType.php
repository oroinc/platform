<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Symfony\Component\Form\CallbackTransformer;

class SystemEmailTemplateSelectType extends AbstractType
{
    /**
     * @var ObjectManager
     */
    protected $em;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ObjectManager $objectManager
     * @param SecurityFacade $securityFacade
     */
    public function __construct(ObjectManager $objectManager, SecurityFacade $securityFacade)
    {
        $this->em  = $objectManager;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'query_builder' => $this->getRepository()->getEntityTemplatesQueryBuilder(
                '',
                $this->securityFacade->getOrganization(),
                true
            ),
            'class' => 'OroEmailBundle:EmailTemplate',
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
                function ($emailtTemplate) {
                    if (is_null($emailtTemplate)) {
                        return '';
                    }
                    return $emailtTemplate->getName();
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_email_system_template_list';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_translatable_entity';
    }

    /**
     * @return EmailTemplateRepository
     */
    protected function getRepository()
    {
        return $this->em->getRepository('OroEmailBundle:EmailTemplate');
    }
}
