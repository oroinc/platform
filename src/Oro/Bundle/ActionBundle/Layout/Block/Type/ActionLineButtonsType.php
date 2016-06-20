<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

class ActionLineButtonsType extends AbstractType
{
    const NAME = 'action_line_buttons';

    /**
     * @var ApplicationsHelper
     */
    protected $applicationsHelper;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param ApplicationsHelper $applicationsHelper
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ApplicationsHelper $applicationsHelper, DoctrineHelper $doctrineHelper)
    {
        $this->applicationsHelper = $applicationsHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'dialogRoute' => $this->applicationsHelper->getDialogRoute(),
                'executionRoute' => $this->applicationsHelper->getExecutionRoute(),
                'attr' => [
                    'data-page-component-module' => 'oroaction/js/app/components/buttons-component'
                ]
            ]
        )
            ->setRequired(['actions'])
            ->setDefined(['entity', 'entityClass']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        if (array_key_exists('visible', $options) && !$options['visible']) {
            return;
        }
        if (empty($options['entity']) && empty($options['entityClass'])) {
            throw new LogicException(
                'entity or entityClass must be provided'
            );
        }

        $view->vars['actions'] = $options['actions'];
        $view->vars['dialogRoute'] = $options['dialogRoute'];
        $view->vars['executionRoute'] = $options['executionRoute'];
        $view->vars['attr'] = $options['attr'];

        if (!empty($options['entity']) && is_object($options['entity'])) {
            $view->vars['entityClass'] = ClassUtils::getClass($options['entity']);
            $view->vars['entityId'] = $this->doctrineHelper->getSingleEntityIdentifier($options['entity']);
        } else {
            $view->vars['entityClass'] = ClassUtils::getRealClass($options['entityClass']);
            $view->vars['entityId'] = null;
        }
    }
}
