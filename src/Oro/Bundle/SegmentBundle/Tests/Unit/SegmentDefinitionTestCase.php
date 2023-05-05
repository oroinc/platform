<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTestCase;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\ReflectionUtil;

class SegmentDefinitionTestCase extends OrmQueryConverterTestCase
{
    protected const TEST_ENTITY = 'AcmeBundle:UserEntity';
    protected const TEST_IDENTIFIER_NAME = 'id';
    protected const TEST_IDENTIFIER = 32;

    public function getSegment(array $definition = null, string $entity = null, int $identifier = null): Segment
    {
        $segment = new Segment();
        ReflectionUtil::setId($segment, $identifier ?? self::TEST_IDENTIFIER);
        $segment->setEntity($entity ?? self::TEST_ENTITY);
        $segment->setDefinition(QueryDefinitionUtil::encodeDefinition($definition ?? $this->getDefaultDefinition()));

        return $segment;
    }

    protected function getDefaultDefinition(): array
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
