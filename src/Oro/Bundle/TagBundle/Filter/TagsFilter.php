<?php

namespace Oro\Bundle\TagBundle\Filter;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class TagsFilter extends EntityFilter
{
    /** @var Registry */
    protected $doctrine;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility        $util
     * @param Registry             $doctrine
     * @param AclHelper            $aclHelper
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        Registry $doctrine,
        AclHelper $aclHelper
    ) {
        parent::__construct($factory, $util);
        $this->doctrine  = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'oro_type_tag_filter';
        if (isset($params['null_value'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['null_value'] = $params['null_value'];
        }
        $this->name   = $name;
        $this->params = $params;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            throw new \LogicException(
                sprintf(
                    '"Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter" expected but "%s" given.',
                    get_class($ds)
                )
            );
        }

        $className = $this->get('options')['field_options']['entity_class'];
        $qb        = $ds->getQueryBuilder();
        $em        = $qb->getEntityManager();

        $tagsArray = $data['value']->toArray();
        $tagsIds   = array_map(
            function ($tag) {
                return $tag->getId();
            },
            $tagsArray
        );

        $subQuery = $em->getRepository('OroTagBundle:Tagging')
            ->createQueryBuilder('tagging')
            ->select('tagging.recordId')
            ->join('tagging.tag', 'tag')
            ->where('tagging.entityName = :entity_class_name')
            ->andWhere($qb->expr()->in('tag.id', $tagsIds))
            ->setParameter('entity_class_name', $className)
            ->getDQL();
        $this->applyFilterToClause($ds, $ds->expr()->in($qb->getRootAliases()[0] . '.id', $subQuery));
        $qb->setParameter('entity_class_name', $className);
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        if (!$this->form) {
            $this->form = $this->formFactory->create(
                $this->getFormType(),
                null,
                ['entity_class' => $this->get('options')['field_options']['entity_class']]
            );
        }

        return $this->form;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return 'oro_type_tag_filter';
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $formView  = $this->getForm()->createView();
        $fieldView = $formView->children['value'];

        return [
            'translatable' => true,
            'label'        => 'oro.tag.entity_plural_label',
            'choices'      => $fieldView->vars['choices'],
            'type'         => 'multichoice',
            'name'         => $this->name
        ];
    }
}
