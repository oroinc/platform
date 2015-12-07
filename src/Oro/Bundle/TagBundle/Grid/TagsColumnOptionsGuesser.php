<?php

namespace Oro\Bundle\TagBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\AbstractColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\TagBundle\Provider\TagVirtualFieldProvider;

class TagsColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    public function guessFilter($class, $property, $type)
    {
        if ($property === TagVirtualFieldProvider::TAG_FIELD ) {
            return new ColumnGuess(
                [
                    'type'      => 'tag',
                    'label'     => 'oro.tag.entity_plural_label',
                    'data_name' => 'tag.id',
                    'enabled'   => true,
                    'options'   => [
                        'field_options' => [
                            'entity_class' => $class,
                        ]
                    ]
                ],
                ColumnGuess::HIGH_CONFIDENCE
            );
        }

        return null;
    }
}
