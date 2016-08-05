<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Type\AbstractConfigType;

class TagConfigChoiceType extends AbstractConfigType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(
            [
                'empty_value' => false,
                'choices'     => ['No', 'Yes']
            ]
        );

        $resolver->setNormalizers(
            [
                'empty_value' => function (Options $options, $value) {
                    return $this->isImplementsTaggable($options) ? 'Yes' : $value;
                },
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
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
        return 'oro_tag_config_choice';
    }

    /**
     * {@inheritdoc}
     */
    protected function isReadOnly($options)
    {
        return $this->isImplementsTaggable($options) || parent::isReadOnly($options);
    }

    /**
     * @param $options
     *
     * @return bool
     */
    protected function isImplementsTaggable($options)
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
