<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit;

use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTest;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentDefinitionTestCase extends OrmQueryConverterTest
{
    protected const TEST_ENTITY          = 'AcmeBundle:UserEntity';
    protected const TEST_IDENTIFIER_NAME = 'id';
    protected const TEST_IDENTIFIER      = 32;

    /**
     * @param array|null  $definition
     * @param string|null $entity
     * @param int|null    $identifier
     *
     * @return Segment
     */
    public function getSegment(array $definition = null, string $entity = null, bool $identifier = null)
    {
        $segment = new Segment();
        $segment->setEntity($entity ?? self::TEST_ENTITY);
        $segment->setDefinition(json_encode($definition ?? $this->getDefaultDefinition(), JSON_THROW_ON_ERROR));

        $refProperty = new \ReflectionProperty(get_class($segment), 'id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($segment, $identifier ?? self::TEST_IDENTIFIER);

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
