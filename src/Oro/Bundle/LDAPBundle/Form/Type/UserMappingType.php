<?php

namespace Oro\Bundle\LDAPBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserMappingType extends AbstractType
{
    const USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';

    /** @var Registry */
    protected $registry;

    /** @var array */
    protected $requiredFields = [
        'username',
        'email',
    ];

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $userManager = $this->getUserManager();
        $metadata = $userManager->getClassMetadata(static::USER_CLASS);
        $notRequiredFields = array_diff($metadata->getFieldNames(), $this->requiredFields);

        $requiredOptions = [
            'required'    => true,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ];

        $this->addFields($builder, $this->requiredFields, $requiredOptions);
        $this->addFields($builder, $notRequiredFields);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $fields
     * @param array $options
     */
    protected function addFields(FormBuilderInterface $builder, array $fields, array $options = [])
    {
        foreach ($fields as $field) {
            $fieldOptions = array_merge(
                [
                    'label'    => $field,
                    'required' => false,
                ],
                $options
            );
            $builder->add($field, 'text', $fieldOptions);
        }
    }

    /**
     * @return EntityManager
     */
    protected function getUserManager()
    {
        return $this->registry->getManagerForClass(static::USER_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_ldap_user_mapping';
    }
}
