<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SecurityBundle\Form\Model\Share;

class ShareScopeType extends AbstractType
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->getChoices(),
            ]
        );
        $resolver->setNormalizers(
            [
                'disabled' => function (Options $options, $value) {
                    return $this->isReadOnly($options) ? true : $value;
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
        return 'oro_share_scope';
    }

    /**
     * @return array
     */
    protected function getChoices()
    {
        return [
            Share::SHARE_SCOPE_USER => 'oro.security.share_scopes.user.label',
            Share::SHARE_SCOPE_BUSINESS_UNIT => 'oro.security.share_scopes.business_unit.label',
        ];
    }

    /**
     * Checks if the form type should be read-only or not
     *
     * @param $options
     *
     * @return bool
     */
    protected function isReadOnly($options)
    {
        /** @var EntityConfigId $configId */
        $configId = $options['config_id'];
        $className = $configId->getClassName();

        if (!empty($className)) {
            $shareScopes = $this->configManager->getProvider('security')->getConfig($className)->get('share_scopes');
            // do not set as read-only if value is empty array - checkboxes were unchecked, but entity supports sharing
            // set as read-only if value equivalent to null - entity doesn't support sharing
            if ($shareScopes !== null) {
                return false;
            }
        }

        return true;
    }
}
