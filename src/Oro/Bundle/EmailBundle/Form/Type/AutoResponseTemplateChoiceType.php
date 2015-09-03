<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class AutoResponseTemplateChoiceType extends AbstractType
{
    const NAME = 'oro_email_autoresponse_template_choice';

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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'selectedEntity' => Email::ENTITY_CLASS,
            'query_builder' => function (EmailTemplateRepository $repository) {
                return $repository->getEntityTemplatesQueryBuilder(
                    Email::ENTITY_CLASS,
                    $this->securityFacade->getOrganization()
                );
            },
            'configs' => [
                'allowClear'  => true,
                'placeholder' => 'oro.form.custom_value',
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_email_template_list';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
