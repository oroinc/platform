<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Entity\TestDefaultAndNull;

class RestJsonApiDefaultValuesTest extends DefaultAndNullTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function sendCreateRequest(array $data, $expectedStatusCode = 201)
    {
        $data['data']['attributes']['withNotBlank'] = 'value';
        $data['data']['attributes']['withNotNull'] = 'value';

        return parent::sendCreateRequest($data, $expectedStatusCode);
    }

    /**
     * {@inheritdoc}
     */
    protected function sendUpdateRequest($entityId, array $data, $expectedStatusCode = 200)
    {
        $data['data']['attributes']['withNotBlank'] = 'value';
        $data['data']['attributes']['withNotNull'] = 'value';

        return parent::sendUpdateRequest($entityId, $data, $expectedStatusCode);
    }

    public function testCreateShouldUseDefaultValues()
    {
        $data = [
            'data' => [
                'attributes' => [
                ],
            ]
        ];

        $response = $this->sendCreateRequest($data);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals('default', $result['data']['attributes']['withDefaultValueString']);
        self::assertFalse($result['data']['attributes']['withDefaultValueBoolean']);
        self::assertSame(0, $result['data']['attributes']['withDefaultValueInteger']);

        $entity = $this->loadTestEntity((int)$result['data']['id']);
        self::assertEquals('default', $entity->withDefaultValueString);
        self::assertFalse($entity->withDefaultValueBoolean);
        self::assertSame(0, $entity->withDefaultValueInteger);
    }

    public function testCreateShouldOverrideDefaultValues()
    {
        $data = [
            'data' => [
                'attributes' => [
                    'withDefaultValueString'  => 'value',
                    'withDefaultValueBoolean' => true,
                    'withDefaultValueInteger' => 123,
                ],
            ]
        ];

        $response = $this->sendCreateRequest($data);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals('value', $result['data']['attributes']['withDefaultValueString']);
        self::assertTrue($result['data']['attributes']['withDefaultValueBoolean']);
        self::assertSame(123, $result['data']['attributes']['withDefaultValueInteger']);

        $entity = $this->loadTestEntity((int)$result['data']['id']);
        self::assertEquals('value', $entity->withDefaultValueString);
        self::assertTrue($entity->withDefaultValueBoolean);
        self::assertSame(123, $entity->withDefaultValueInteger);
    }

    public function testCreateShouldOverrideDefaultValueWithNull()
    {
        $data = [
            'data' => [
                'attributes' => [
                    'withDefaultValueString'  => null,
                    'withDefaultValueBoolean' => null,
                    'withDefaultValueInteger' => null,
                ],
            ]
        ];

        $response = $this->sendCreateRequest($data);

        $result = self::jsonToArray($response->getContent());
        self::assertNull($result['data']['attributes']['withDefaultValueString']);
        self::assertNull($result['data']['attributes']['withDefaultValueBoolean']);
        self::assertNull($result['data']['attributes']['withDefaultValueInteger']);

        $entity = $this->loadTestEntity((int)$result['data']['id']);
        self::assertNull($entity->withDefaultValueString);
        self::assertNull($entity->withDefaultValueBoolean);
        self::assertNull($entity->withDefaultValueInteger);
    }

    public function testUpdateShouldKeepChangedDefaultValuesAsIs()
    {
        $entity = new TestDefaultAndNull();
        $entity->withDefaultValueString = 'value';
        $entity->withDefaultValueBoolean = true;
        $entity->withDefaultValueInteger = 123;
        $this->saveTestEntity($entity);

        $data = [
            'data' => [
                'attributes' => [
                ],
            ]
        ];

        $response = $this->sendUpdateRequest($entity->id, $data);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals('value', $result['data']['attributes']['withDefaultValueString']);
        self::assertTrue($result['data']['attributes']['withDefaultValueBoolean']);
        self::assertSame(123, $result['data']['attributes']['withDefaultValueInteger']);

        $entity = $this->loadTestEntity((int)$result['data']['id']);
        self::assertEquals('value', $entity->withDefaultValueString);
        self::assertTrue($entity->withDefaultValueBoolean);
        self::assertSame(123, $entity->withDefaultValueInteger);
    }
}
