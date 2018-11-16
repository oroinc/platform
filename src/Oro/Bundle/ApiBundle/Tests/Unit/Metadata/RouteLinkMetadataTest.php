<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RouteLinkMetadataTest extends \PHPUnit\Framework\TestCase
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    protected function setUp()
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    }

    public function testClone()
    {
        $linkMetadata = new RouteLinkMetadata(
            $this->urlGenerator,
            'routeName',
            ['key1' => 'value1'],
            ['key2' => 'value2']
        );
        $linkMetadata->addMetaProperty(new MetaAttributeMetadata('metaProperty1', 'string'));

        $linkMetadataClone = clone $linkMetadata;

        self::assertEquals($linkMetadata, $linkMetadataClone);
    }

    public function testToArray()
    {
        $linkMetadata = new RouteLinkMetadata(
            $this->urlGenerator,
            'testRouteName',
            ['key1' => 'value1'],
            ['key2' => 'value2']
        );
        $linkMetadata->addMetaProperty(new MetaAttributeMetadata('metaProperty1', 'string'));

        self::assertEquals(
            [
                'route_name'      => 'testRouteName',
                'route_params'    => ['key1' => 'value1'],
                'default_params'  => ['key2' => 'value2'],
                'meta_properties' => [
                    'metaProperty1' => [
                        'data_type' => 'string'
                    ]
                ]
            ],
            $linkMetadata->toArray()
        );
    }

    public function testToArrayWithRequiredPropertiesOnly()
    {
        $linkMetadata = new RouteLinkMetadata($this->urlGenerator, 'testRouteName');

        self::assertEquals(
            [
                'route_name' => 'testRouteName'
            ],
            $linkMetadata->toArray()
        );
    }

    public function testGetHrefWithoutRouteParams()
    {
        $linkMetadata = new RouteLinkMetadata($this->urlGenerator, 'routeName');
        $url = 'http://test.com/api';

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::never())
            ->method('tryGetValue');

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'routeName',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($url);

        self::assertEquals($url, $linkMetadata->getHref($dataAccessor));
    }

    public function testGetHrefWhenAllRouteParamsAreResolved()
    {
        $linkMetadata = new RouteLinkMetadata(
            $this->urlGenerator,
            'routeName',
            ['resource' => DataAccessorInterface::ENTITY_TYPE, 'filter' => null],
            ['key1' => 'value1']
        );
        $url = 'http://test.com/api/{version}/entity?filter=123';

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::exactly(2))
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (DataAccessorInterface::ENTITY_TYPE === $propertyPath) {
                    $value = 'entity';
                    $hasValue = true;
                } elseif ('filter' === $propertyPath) {
                    $value = 123;
                    $hasValue = true;
                }

                return $hasValue;
            });

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'routeName',
                ['resource' => 'entity', 'filter' => 123, 'key1' => 'value1'],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($url);

        self::assertEquals($url, $linkMetadata->getHref($dataAccessor));
    }

    public function testGetHrefWhenOnlyPartOfRouteParamsAreResolvedButThereAreDefaultValuesInDefaultParams()
    {
        $linkMetadata = new RouteLinkMetadata(
            $this->urlGenerator,
            'routeName',
            ['resource' => DataAccessorInterface::ENTITY_TYPE, 'filter' => null],
            ['key1' => 'value1', 'filter' => 'defaultFilter']
        );
        $url = 'http://test.com/api/{version}/entity?filter=defaultFilter';

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::exactly(2))
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (DataAccessorInterface::ENTITY_TYPE === $propertyPath) {
                    $value = 'entity';
                    $hasValue = true;
                }

                return $hasValue;
            });

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'routeName',
                ['resource' => 'entity', 'filter' => 'defaultFilter', 'key1' => 'value1'],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($url);

        self::assertEquals($url, $linkMetadata->getHref($dataAccessor));
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\LinkHrefResolvingFailedException
     * @expectedExceptionMessage Cannot build URL for a link. Reason: an error
     */
    public function testGetHrefWhenOnlyPartOfRouteParamsAreResolvedAndInvalidParameterExceptionIsThrown()
    {
        $exception = new InvalidParameterException('an error');

        $linkMetadata = new RouteLinkMetadata(
            $this->urlGenerator,
            'routeName',
            ['resource' => DataAccessorInterface::ENTITY_TYPE, 'filter' => null, 'version' => '_.version']
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::exactly(3))
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (DataAccessorInterface::ENTITY_TYPE === $propertyPath) {
                    $value = 'entity';
                    $hasValue = true;
                }

                return $hasValue;
            });

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'routeName',
                ['resource' => 'entity'],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willThrowException($exception);

        self::assertNull($linkMetadata->getHref($dataAccessor));
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\LinkHrefResolvingFailedException
     * @expectedExceptionMessage Cannot build URL for a link. Reason: an error
     */
    public function testGetHrefWhenOnlyPartOfRouteParamsAreResolvedAndMissingMandatoryParametersExceptionIsThrown()
    {
        $exception = new MissingMandatoryParametersException('an error');

        $linkMetadata = new RouteLinkMetadata(
            $this->urlGenerator,
            'routeName',
            ['resource' => DataAccessorInterface::ENTITY_TYPE, 'filter' => null, 'version' => '_.version']
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::exactly(3))
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (DataAccessorInterface::ENTITY_TYPE === $propertyPath) {
                    $value = 'entity';
                    $hasValue = true;
                }

                return $hasValue;
            });

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'routeName',
                ['resource' => 'entity'],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willThrowException($exception);

        self::assertNull($linkMetadata->getHref($dataAccessor));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetHrefWhenUnhandledExceptionIsThrown()
    {
        $exception = new \InvalidArgumentException('an error');

        $linkMetadata = new RouteLinkMetadata($this->urlGenerator, 'routeName');

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::never())
            ->method('tryGetValue');

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'routeName',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willThrowException($exception);

        self::assertNull($linkMetadata->getHref($dataAccessor));
    }

    public function testMetaProperties()
    {
        $linkMetadata = new RouteLinkMetadata($this->urlGenerator, 'routeName');
        self::assertCount(0, $linkMetadata->getMetaProperties());
        self::assertFalse($linkMetadata->hasMetaProperty('unknown'));
        self::assertNull($linkMetadata->getMetaProperty('unknown'));

        $metaProperty1 = new MetaAttributeMetadata('metaProperty1', 'string');
        self::assertSame($metaProperty1, $linkMetadata->addMetaProperty($metaProperty1));
        $metaProperty2 = new MetaAttributeMetadata('metaProperty2', 'string');
        self::assertSame($metaProperty2, $linkMetadata->addMetaProperty($metaProperty2));
        self::assertCount(2, $linkMetadata->getMetaProperties());

        self::assertTrue($linkMetadata->hasMetaProperty('metaProperty1'));
        self::assertSame($metaProperty1, $linkMetadata->getMetaProperty('metaProperty1'));

        $linkMetadata->removeMetaProperty('metaProperty1');
        self::assertCount(1, $linkMetadata->getMetaProperties());
        self::assertFalse($linkMetadata->hasMetaProperty('metaProperty1'));
        self::assertTrue($linkMetadata->hasMetaProperty('metaProperty2'));

        $linkMetadata->removeMetaProperty('metaProperty2');
        self::assertCount(0, $linkMetadata->getMetaProperties());
        self::assertFalse($linkMetadata->hasMetaProperty('metaProperty2'));
    }
}
