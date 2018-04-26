<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;

class AttributeGuesser extends AbstractGuesser
{
    /**
     * @var FormTypeGuesserInterface
     */
    protected $formTypeGuesser;

    /**
     * @param Attribute $attribute
     * @return null|TypeGuess
     */
    public function guessAttributeForm(Attribute $attribute)
    {
        $attributeType = $attribute->getType();
        if ($attributeType === 'entity') {
            list($formType, $formOptions) = $this->getEntityForm($attribute->getOption('class'));
        } elseif (isset($this->formTypeMapping[$attributeType])) {
            $formType = $this->formTypeMapping[$attributeType]['type'];
            $formOptions = $this->formTypeMapping[$attributeType]['options'];
        } else {
            return null;
        }

        return new TypeGuess($formType, $formOptions, TypeGuess::VERY_HIGH_CONFIDENCE);
    }

    /**
     * @param string $entityClass
     * @return array
     */
    protected function getEntityForm($entityClass)
    {
        $formType = null;
        $formOptions = array();
        if ($this->formConfigProvider->hasConfig($entityClass)) {
            $formConfig = $this->formConfigProvider->getConfig($entityClass);
            $formType = $formConfig->get('form_type');
            $formOptions = $formConfig->get('form_options', false, array());
        }
        if (!$formType) {
            $formType = EntityType::class;
            $formOptions = array(
                'class' => $entityClass,
                'multiple' => false,
            );
        }

        return array($formType, $formOptions);
    }

    /**
     * @param string $rootClass
     * @param Attribute $attribute
     * @return null|TypeGuess
     */
    public function guessClassAttributeForm($rootClass, Attribute $attribute)
    {
        $propertyPath = $attribute->getPropertyPath();
        if (!$propertyPath) {
            return $this->guessAttributeForm($attribute);
        }

        $attributeParameters = $this->guessMetadataAndField($rootClass, $propertyPath);
        if (!$attributeParameters) {
            return $this->guessAttributeForm($attribute);
        }

        /** @var ClassMetadata $metadata */
        $metadata = $attributeParameters['metadata'];
        $class = $metadata->getName();
        $field = $attributeParameters['field'];

        return $this->getFormTypeGuesser()->guessType($class, $field);
    }

    /**
     * @return FormTypeGuesserInterface
     */
    protected function getFormTypeGuesser()
    {
        if (!$this->formTypeGuesser) {
            $this->formTypeGuesser = $this->formRegistry->getTypeGuesser();
        }

        return $this->formTypeGuesser;
    }
}
