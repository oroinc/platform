<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;

/**
 * This widget must not has any model transformers that converts entities identifiers to object because it
 * stores model data in database widget options.
 * {@see Oro\Bundle\DashboardBundle\Controller\DashboardController::configureAction}
 */
class WidgetEntityJquerySelect2HiddenType extends OroJquerySelect2HiddenType
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param EntityManager  $entityManager
     * @param SearchRegistry $registry
     * @param ConfigProvider $configProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        EntityManager $entityManager,
        SearchRegistry $registry,
        ConfigProvider $configProvider,
        DoctrineHelper $doctrineHelper
    ) {

        parent::__construct($entityManager, $registry, $configProvider);
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                if (is_array($data)) {
                    $data = array_filter($data);
                    $event->setData($data);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $vars = [
            'configs'  => $options['configs'],
            'excluded' => (array)$options['excluded']
        ];

        if ($form->getData()) {
            $result = [];
            /** @var ConverterInterface $converter */
            $converter = $options['converter'];
            if (isset($options['configs']['multiple']) && $options['configs']['multiple']) {
                $ids = $form->getData();
            } else {
                $ids = [$form->getData()];
            }
            $items = $this->getEntitiesByIdentifiers($options['entity_class'], $ids);

            $ids = [];
            foreach ($items as $item) {
                $result[] = $converter->convertItem($item);
                $ids[] = $this->doctrineHelper->getSingleEntityIdentifier($item);
            }

            $vars['value'] = implode(',', $ids);
            $vars['attr']  = [
                'data-selected-data' => json_encode($result)
            ];
        }

        $view->vars = array_replace_recursive($view->vars, $vars);
    }

    /**
     * @param string $entityClass
     * @param array  $ids
     *
     * @return array
     */
    protected function getEntitiesByIdentifiers($entityClass, array $ids)
    {
        $ids = array_filter($ids);
        if (empty($ids)) {
            return [];
        }

        $identityField = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);

        return $this->entityManager->getRepository($entityClass)->findBy([$identityField => $ids]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_widget_entity_jqueryselect2_hidden';
    }
}
