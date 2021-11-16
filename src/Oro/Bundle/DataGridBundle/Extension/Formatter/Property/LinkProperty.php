<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * Datagrid property formatter that renders a URL as an <a> HTML tag.
 */
class LinkProperty extends UrlProperty
{
    const TEMPLATE = '@OroDataGrid/Extension/Formatter/Property/linkProperty.html.twig';

    /**
     * @var Environment
     */
    protected $twig;

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
        $link = null;

        try {
            $label = $record->getValue($this->getOr(self::DATA_NAME_KEY, $this->get(self::NAME_KEY)));
        } catch (\LogicException $e) {
        }

        try {
            $link = parent::getRawValue($record);
        } catch (InvalidParameterException $e) {
        }

        return $this->twig->render(
            self::TEMPLATE,
            [
                'url' => $link,
                'label' => $label
            ]
        );
    }
}
