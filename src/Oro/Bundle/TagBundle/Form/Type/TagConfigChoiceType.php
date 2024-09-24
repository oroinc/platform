<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Type\AbstractConfigType;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A form type to toggle tags functionality
 */
class TagConfigChoiceType extends AbstractConfigType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'placeholder' => false,
                'choices' => [
                    'No' => false,
                    'Yes' => true,
                ],
            ]
        );

        $resolver->setNormalizer(
            'placeholder',
            function (Options $options, $value) {
                return $this->isImplementsTaggable($options) ? 'Yes' : $value;
            }
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_tag_config_choice';
    }

    #[\Override]
    protected function isReadOnly(Options $options)
    {
        return $this->isImplementsTaggable($options) || parent::isReadOnly($options);
    }

    /**
     * @param Options $options
     *
     * @return bool
     */
    protected function isImplementsTaggable(Options $options)
    {
        /** @var EntityConfigId $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        if (!empty($className)) {
            return TaggableHelper::isImplementsTaggable($className);
        }

        return false;
    }
}
