<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

class WidgetBusinessUnitSearchHandler extends SearchHandler
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var bool */
    protected $addCurrent = false;

    /**
     * @param TranslatorInterface $translator
     * @param array               $entityName
     * @param array               $properties
     */
    public function __construct(
        TranslatorInterface $translator,
        $entityName,
        array $properties
    ) {
        parent::__construct($entityName, $properties);

        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        $page = (int)$page > 0 ? (int)$page : 1;
        if ($page === 1) {
            $this->addCurrent = true;
        }

        return parent::search($query, $page, $perPage, $searchById);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertItems(array $items)
    {
        $result = parent::convertItems($items);

        $current = array_filter(
            $result,
            function ($item) {
                return $item[$this->idFieldName] === OwnerHelper::CURRENT_BUSINESS_UNIT;
            }
        );
        if (empty($current) && $this->addCurrent) {
            $text = reset($this->properties);
            $current = [
                $this->idFieldName => OwnerHelper::CURRENT_BUSINESS_UNIT,
                $text => $this->translator->trans('oro.business_unit.dashboard.current_business_unit'),
            ];
            array_unshift($result, $current);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        if ($this->idFieldName) {
            if (is_array($item)) {
                if ($item[$this->idFieldName] === OwnerHelper::CURRENT_BUSINESS_UNIT) {
                    $text = reset($this->properties);
                    $current = [
                        $this->idFieldName => OwnerHelper::CURRENT_BUSINESS_UNIT,
                        $text => $this->translator->trans('oro.business_unit.dashboard.current_business_unit'),
                    ];

                    return $current;
                }
            }
        }

        return parent::convertItem($item);
    }
}
