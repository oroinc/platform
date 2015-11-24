<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\TagBundle\Entity\TagManager;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Type\AbstractConfigType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;

class TagConfigChoiceType extends AbstractConfigType
{
    /** @var TagManager */
    protected $tagManager;

    /**
     * @param AssociationTypeHelper $typeHelper
     * @param TagManager            $tagManager
     */
    public function __construct(AssociationTypeHelper $typeHelper, TagManager $tagManager)
    {
        parent::__construct($typeHelper);
        $this->tagManager = $tagManager;
    }

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
            return $this->tagManager->isImplementsTaggable($className);
        }

        return false;
    }
}
