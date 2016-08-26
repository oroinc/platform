<?php

namespace Oro\Bundle\FeatureToggleBundle\Layout\Block;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class FeatureToggleExtension extends AbstractBlockTypeExtension
{
    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * FeatureToggleExtension constructor.
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /** {@inheritdoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['feature' => []]);
    }

    /** {@inheritdoc} */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        if (!empty($options['feature'])) {
            $scopeId = null;
            if (!empty($options['feature']['scope'])) {
                $scopeId = $options['feature']['scope'];
            }
            if (!empty($options['feature']['name'])) {
                if (!$this->isFeatureEnabled($options['feature']['name'], $scopeId)) {
                    $view->vars['visible'] = false;
                }
            } elseif (!empty($options['feature']['resource']) && !empty($options['feature']['type'])) {
                if (!$this->isResourceEnabled($options['feature']['resource'], $options['feature']['type'], $scopeId)) {
                    $view->vars['visible'] = false;
                }
            }
        }
    }

    /**
     * @param string $feature
     * @param int|null $scopeIdentifier
     * @return bool
     */
    protected function isFeatureEnabled($feature, $scopeIdentifier = null)
    {
        return $this->featureChecker->isFeatureEnabled($feature, $scopeIdentifier);
    }

    /**
     * @param string $resource
     * @param string $resourceType
     * @param int|null $scopeIdentifier
     * @return bool
     */
    protected function isResourceEnabled($resource, $resourceType, $scopeIdentifier = null)
    {
        return $this->featureChecker->isResourceEnabled($resource, $resourceType, $scopeIdentifier);
    }

    /** {@inheritdoc} */
    public function getExtendedType()
    {
        return BaseType::NAME;
    }
}
