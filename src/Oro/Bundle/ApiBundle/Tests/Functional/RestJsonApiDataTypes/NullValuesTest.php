<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiDataTypes;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDefaultAndNull;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class NullValuesTest extends DefaultAndNullTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function sendCreateRequest(array $data, bool $assertValid = true): Response
    {
        $data['data']['attributes']['withNotBlank'] = 'value';
        $data['data']['attributes']['withNotNull'] = 'value';

        return parent::sendCreateRequest($data, $assertValid);
    }

    /**
     * {@inheritdoc}
     */
    protected function sendUpdateRequest(int $entityId, array $data, bool $assertValid = true): Response
    {
        $data['data']['attributes']['withNotBlank'] = 'value';
        $data['data']['attributes']['withNotNull'] = 'value';

        return parent::sendUpdateRequest($entityId, $data, $assertValid);
    }

    public function testCreateShouldSetNullValue()
    {
        $data = [
            'data' => [
                'attributes' => [
                    'withoutDefaultValueString'  => null,
                    'withoutDefaultValueBoolean' => null,
                    'withoutDefaultValueInteger' => null,
                ],
            ]
        ];

        $response = $this->sendCreateRequest($data);

        $result = self::jsonToArray($response->getContent());
        self::assertNull($result['data']['attributes']['withoutDefaultValueString']);
        self::assertNull($result['data']['attributes']['withoutDefaultValueBoolean']);
        self::assertNull($result['data']['attributes']['withoutDefaultValueInteger']);

        $entity = $this->loadTestEntity((int)$result['data']['id']);
        self::assertNull($entity->withoutDefaultValueString);
        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ($this->isPostgreSql()) {
            self::assertFalse($entity->withoutDefaultValueBoolean);
        } else {
            self::assertNull($entity->withoutDefaultValueBoolean);
        }
        self::assertNull($entity->withoutDefaultValueInteger);
    }

    public function testUpdateShouldSetNullValue()
    {
        $entity = new TestDefaultAndNull();
        $entity->withoutDefaultValueString = 'value';
        $entity->withoutDefaultValueBoolean = true;
        $entity->withoutDefaultValueInteger = 123;
        $this->saveTestEntity($entity);

        $data = [
            'data' => [
                'attributes' => [
                    'withoutDefaultValueString'  => null,
                    'withoutDefaultValueBoolean' => null,
                    'withoutDefaultValueInteger' => null,
                ],
            ]
        ];

        $response = $this->sendUpdateRequest($entity->id, $data);

        $result = self::jsonToArray($response->getContent());
        self::assertNull($result['data']['attributes']['withoutDefaultValueString']);
        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ($this->isPostgreSql()) {
            self::assertFalse($result['data']['attributes']['withoutDefaultValueBoolean']);
        } else {
            self::assertNull($result['data']['attributes']['withoutDefaultValueBoolean']);
        }
        self::assertNull($result['data']['attributes']['withoutDefaultValueInteger']);

        $entity = $this->loadTestEntity((int)$result['data']['id']);
        self::assertNull($entity->withoutDefaultValueString);
        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ($this->isPostgreSql()) {
            self::assertFalse($entity->withoutDefaultValueBoolean);
        } else {
            self::assertNull($entity->withoutDefaultValueBoolean);
        }
        self::assertNull($entity->withoutDefaultValueInteger);
    }

    public function testCreateShouldSetEmptyAndZeroValues()
    {
        $data = [
            'data' => [
                'attributes' => [
                    'withoutDefaultValueString'  => '',
                    'withoutDefaultValueBoolean' => false,
                    'withoutDefaultValueInteger' => 0,
                ],
            ]
        ];

        $response = $this->sendCreateRequest($data);

        $result = self::jsonToArray($response->getContent());
        self::assertSame('', $result['data']['attributes']['withoutDefaultValueString']);
        self::assertFalse($result['data']['attributes']['withoutDefaultValueBoolean']);
        self::assertSame(0, $result['data']['attributes']['withoutDefaultValueInteger']);

        $entity = $this->loadTestEntity((int)$result['data']['id']);
        self::assertSame('', $entity->withoutDefaultValueString);
        self::assertFalse($entity->withoutDefaultValueBoolean);
        self::assertSame(0, $entity->withoutDefaultValueInteger);
    }

    public function testUpdateShouldSetEmptyAndZeroValues()
    {
        $entity = new TestDefaultAndNull();
        $entity->withoutDefaultValueString = 'value';
        $this->saveTestEntity($entity);

        $data = [
            'data' => [
                'attributes' => [
                    'withoutDefaultValueString'  => '',
                    'withoutDefaultValueBoolean' => false,
                    'withoutDefaultValueInteger' => 0,
                ],
            ]
        ];

        $response = $this->sendUpdateRequest($entity->id, $data);

        $result = self::jsonToArray($response->getContent());
        self::assertSame('', $result['data']['attributes']['withoutDefaultValueString']);
        self::assertFalse($result['data']['attributes']['withoutDefaultValueBoolean']);
        self::assertSame(0, $result['data']['attributes']['withoutDefaultValueInteger']);

        $entity = $this->loadTestEntity((int)$result['data']['id']);
        self::assertSame('', $entity->withoutDefaultValueString);
        self::assertFalse($entity->withoutDefaultValueBoolean);
        self::assertSame(0, $entity->withoutDefaultValueInteger);
    }
}
