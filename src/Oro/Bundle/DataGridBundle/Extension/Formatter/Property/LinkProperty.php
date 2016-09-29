<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\UIBundle\Twig\Environment;

class LinkProperty extends UrlProperty
{
    const TEMPLATE = 'OroDataGridBundle:Extension:Formatter/Property/linkProperty.html.twig';

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @param RouterInterface $router
     * @param Environment     $twig
     */
    public function __construct(RouterInterface $router, Environment $twig)
    {
        $this->router = $router;
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function getRawValue(ResultRecordInterface $record)
    {
        $label = null;

        try {
            $label = $record->getValue($this->getOr(self::DATA_NAME_KEY) ?: $this->get(self::NAME_KEY));
        } catch (\LogicException $e) {
        }

        return $this->twig
            ->loadTemplate(self::TEMPLATE)
            ->render(
                [
                    'url'   => parent::getRawValue($record),
                    'label' => $label
                ]
            );
    }
}
