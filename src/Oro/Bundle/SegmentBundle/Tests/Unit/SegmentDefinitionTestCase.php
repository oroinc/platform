<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTest;

class SegmentDefinitionTestCase extends OrmQueryConverterTest
{
    const TEST_ENTITY          = 'AcmeBundle:UserEntity';
    const TEST_IDENTIFIER_NAME = 'id';
    const TEST_IDENTIFIER      = 32;

    /**
     * @param bool|string $entity
     * @param array|bool  $definition
     * @param bool|int    $identifier
     *
     * @return Segment
     */
    public function getSegment($entity = false, $definition = false, $identifier = false)
    {
        $segment = new Segment();
        $segment->setEntity(false === $entity ? self::TEST_ENTITY : $entity);
        $segment->setDefinition(json_encode(false === $definition ? $this->getDefaultDefinition() : $definition));

        $refProperty = new \ReflectionProperty(get_class($segment), 'id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($segment, false === $identifier ? self::TEST_IDENTIFIER : $identifier);

        return $segment;
    }

    /**
     * @return array
     */
    protected function getDefaultDefinition()
    {
        return [
            'columns' => [
                [
                    'name'    => 'userName',
                    'label'   => 'User name',
                    'func'    => null,
                    'sorting' => null
                ]
            ],
            'filters' => [
                [
                    'columnName' => 'email',
                    'criterion'  => [
                        'filter' => 'string',
                        'data'   => [
                            'type'  => 4,
                            'value' => '@gmail.com'
                        ]
                    ]
                ]
            ]
        ];
    }
}
