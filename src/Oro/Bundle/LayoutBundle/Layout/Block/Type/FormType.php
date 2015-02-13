<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\Exception\UnexpectedTypeException;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormLayoutBuilderInterface;

/**
 * This block type is responsible to build the layout for a Symfony's form object.
 * Naming convention:
 *  field id = $options['form_field_prefix'] + field path (path separator is replaced with colon (:))
 *      for example: form_firstName or form_address:city  where 'form_' is the prefix
 *  group id = $options['form_group_prefix'] + group name
 *      for example: form:group_myGroup where 'form:group_' is the prefix
 */
class FormType extends AbstractContainerType
{
    const NAME = 'form';

    /** @var FormLayoutBuilderInterface */
    protected $formLayoutBuilder;

    /**
     * @param FormLayoutBuilderInterface $formLayoutBuilder
     */
    public function __construct(FormLayoutBuilderInterface $formLayoutBuilder)
    {
        $this->formLayoutBuilder = $formLayoutBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'form_name'         => 'form',
                // example: ['jobTitle', 'user.lastName']
                'preferred_fields'  => [],
                // example:
                // [
                //   'general'    => [
                //     'title'  => 'General Info',
                //     'fields' => ['user.firstName', 'user.lastName']
                //   ],
                //   'additional'    => [
                //     'title'   => 'Additional Info',
                //     'default' => true
                //   ]
                // ]
                'groups'            => [],
                'form_field_prefix' => null,
                'form_group_prefix' => null
            ]
        );
        $resolver->setAllowedTypes(
            [
                'form_name'         => 'string',
                'preferred_fields'  => 'array',
                'groups'            => 'array',
                'form_field_prefix' => 'string',
                'form_group_prefix' => 'string'
            ]
        );
        $resolver->setNormalizers(
            [
                'form_field_prefix' => function (Options $options, $fieldPrefix) {
                    return $fieldPrefix === null
                        ? $options['form_name'] . '_'
                        : $fieldPrefix;
                },
                'form_group_prefix' => function (Options $options, $fieldPrefix) {
                    return $fieldPrefix === null
                        ? $options['form_name'] . ':group_'
                        : $fieldPrefix;
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, array $options)
    {
        $form = $this->getForm($builder, $options);
        $this->formLayoutBuilder->build($form, $builder, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param BlockBuilderInterface $builder
     * @param array                 $options
     *
     * @return FormInterface
     */
    protected function getForm(BlockBuilderInterface $builder, array $options)
    {
        $form = $builder->getContext()->get($options['form_name']);
        if ($form instanceof FormInterface) {
            // replace the form with the form accessor because child blocks require the accessor
            $builder->getContext()->set($options['form_name'], new FormAccessor($form));
        } elseif ($form instanceof FormAccessorInterface) {
            $form = $form->getForm();
        } else {
            throw new UnexpectedTypeException(
                $form,
                'Symfony\Component\Form\FormInterface or Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface',
                sprintf('context[%s]', $options['form_name'])
            );
        }

        return $form;
    }
}
