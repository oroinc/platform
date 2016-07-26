<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Component\PhpUtils\ArrayUtil;

abstract class AbstractFilter implements FilterInterface
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var FilterUtility */
    protected $util;

    /** @var string */
    protected $name;

    /** @var array */
    protected $params;

    /** @var Form */
    protected $form;

    /** @var array */
    protected $unresolvedOptions = [];

    /** @var array [array, ...] */
    protected $additionalOptions = [];

    /** @var array */
    protected $state;

    /**
     * Constructor
     *
     * @param FormFactoryInterface $factory
     * @param FilterUtility        $util
     */
    public function __construct(FormFactoryInterface $factory, FilterUtility $util)
    {
        $this->formFactory = $factory;
        $this->util        = $util;
    }

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $this->name   = $name;
        $this->params = $params;

        $options = $this->getOr(FilterUtility::FORM_OPTIONS_KEY, []);
        $this->unresolvedOptions = array_filter($options, 'is_callable');
        if (!$this->isLazy()) {
            $this->resolveOptions();
        } else {
            $unresolvedKeys = array_keys($this->unresolvedOptions);
            foreach ($unresolvedKeys as $key) {
                unset($this->params[FilterUtility::FORM_OPTIONS_KEY][$key]);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getForm()
    {
        if (!$this->form) {
            $this->form = $this->formFactory->create(
                $this->getFormType(),
                [],
                array_merge($this->getOr(FilterUtility::FORM_OPTIONS_KEY, []), ['csrf_protection' => false])
            );
        }

        return $this->form;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $formView = $this->getForm()->createView();
        $typeView = $formView->children['type'];

        $defaultMetadata = [
            'name'                     => $this->getName(),
            // use filter name if label not set
            'label'                    => ucfirst($this->name),
            'choices'                  => $typeView->vars['choices'],
        ];

        $metadata = array_diff_key(
            $this->get() ?: [],
            array_flip($this->util->getExcludeParams())
        );
        $metadata = $this->mapParams($metadata);
        $metadata = array_merge($defaultMetadata, $metadata);
        $metadata['lazy'] = $this->isLazy();

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveOptions()
    {
        $this->params[FilterUtility::FORM_OPTIONS_KEY] = array_merge(
            $this->getOr(FilterUtility::FORM_OPTIONS_KEY, []),
            array_map(
                function ($cb) {
                    return call_user_func($cb);
                },
                $this->unresolvedOptions
            )
        );
        $this->unresolvedOptions = [];

        $options = $this->params[FilterUtility::FORM_OPTIONS_KEY];
        foreach ($this->additionalOptions as $path) {
            $options = ArrayUtil::unsetPath($options, $path);
        }
        $this->params[FilterUtility::FORM_OPTIONS_KEY] = $options;
        $this->additionalOptions = [];
    }

    /**
     * Set state of filter
     *
     * @param $state
     *
     * @return $this
     */
    public function setFilterState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state of filter
     *
     * @return mixed
     */
    public function getFilterState()
    {
        return $this->state;
    }

    /**
     * Returns form type associated to this filter
     *
     * @return mixed
     */
    abstract protected function getFormType();

    /**
     * Apply filter expression to having or where clause depending on configuration
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param mixed                            $expression
     * @param string                           $conditionType
     */
    protected function applyFilterToClause(
        FilterDatasourceAdapterInterface $ds,
        $expression,
        $conditionType = FilterUtility::CONDITION_AND
    ) {
        $ds->addRestriction(
            $expression,
            $this->getOr(FilterUtility::CONDITION_KEY, $conditionType),
            $this->getOr(FilterUtility::BY_HAVING_KEY, false)
        );
    }

    /**
     * Get param or throws exception
     *
     * @param string $paramName
     *
     * @throws \LogicException
     * @return mixed
     */
    protected function get($paramName = null)
    {
        $value = $this->params;

        if ($paramName !== null) {
            if (!isset($this->params[$paramName])) {
                throw new \LogicException(sprintf('Trying to access not existing parameter: "%s"', $paramName));
            }

            $value = $this->params[$paramName];
        }

        return $value;
    }

    /**
     * Get param if exists or default value
     *
     * @param string $paramName
     * @param null   $default
     *
     * @return mixed
     */
    protected function getOr($paramName = null, $default = null)
    {
        if ($paramName !== null) {
            return isset($this->params[$paramName]) ? $this->params[$paramName] : $default;
        }

        return $this->params;
    }

    /**
     * Process mapping params
     *
     * @param array $params
     *
     * @return array
     */
    protected function mapParams($params)
    {
        $keys     = [];
        $paramMap = $this->util->getParamMap();
        foreach (array_keys($params) as $key) {
            if (isset($paramMap[$key])) {
                $keys[] = $paramMap[$key];
            } else {
                $keys[] = $key;
            }
        }

        return array_combine($keys, array_values($params));
    }

    /**
     * @return bool
     */
    protected function isLazy()
    {
        $options = $this->getOr(FilterUtility::FORM_OPTIONS_KEY, []);

        return isset($options['lazy']) && $options['lazy'];
    }
}
