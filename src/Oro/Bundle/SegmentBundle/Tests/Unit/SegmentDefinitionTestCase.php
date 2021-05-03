<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTestCase;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\ReflectionUtil;

class SegmentDefinitionTestCase extends OrmQueryConverterTestCase
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
        ReflectionUtil::setId($segment, $identifier ?? self::TEST_IDENTIFIER);
        $segment->setEntity($entity ?? self::TEST_ENTITY);
        $segment->setDefinition(QueryDefinitionUtil::encodeDefinition($definition ?? $this->getDefaultDefinition()));

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
