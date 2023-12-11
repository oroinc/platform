<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiSecurity;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CorsRequestTest extends RestJsonApiTestCase
{
    public function methodsProvider(): array
    {
        return [
            ['ANOTHER'],
            ['OPTIONS'],
            ['GET'],
            ['POST'],
            ['PATCH'],
            ['DELETE']
        ];
    }

    /**
     * @dataProvider methodsProvider
     */
    public function testOptionsPreflightRequestForList(string $requestMethod)
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => $entityType],
            [
                'HTTP_Origin'                        => 'https://api.test.com',
                'HTTP_Access-Control-Request-Method' => $requestMethod
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeader($response, 'Access-Control-Allow-Methods', 'OPTIONS, GET, PATCH, POST, DELETE');
        self::assertResponseHeader($response, 'Access-Control-Allow-Headers', 'Authorization,Content-Type,X-Include');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeader($response, 'Access-Control-Max-Age', 600);
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
        self::assertResponseHeaderNotExists($response, 'Allow');
    }

    /**
     * @dataProvider methodsProvider
     */
    public function testOptionsPreflightRequestForItem(string $requestMethod)
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => $entityType, 'id' => '9999'],
            [
                'HTTP_Origin'                        => 'https://api.test.com',
                'HTTP_Access-Control-Request-Method' => $requestMethod
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeader($response, 'Access-Control-Allow-Methods', 'OPTIONS, GET, PATCH, DELETE');
        self::assertResponseHeader($response, 'Access-Control-Allow-Headers', 'Authorization,Content-Type,X-Include');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeader($response, 'Access-Control-Max-Age', 600);
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
        self::assertResponseHeaderNotExists($response, 'Allow');
    }

    /**
     * @dataProvider methodsProvider
     */
    public function testOptionsPreflightRequestForToOneSubresource(string $requestMethod)
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'owner'],
            [
                'HTTP_Origin'                        => 'https://api.test.com',
                'HTTP_Access-Control-Request-Method' => $requestMethod
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeader($response, 'Access-Control-Allow-Methods', 'OPTIONS, GET');
        self::assertResponseHeader($response, 'Access-Control-Allow-Headers', 'Authorization,Content-Type,X-Include');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeader($response, 'Access-Control-Max-Age', 600);
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
        self::assertResponseHeaderNotExists($response, 'Allow');
    }

    /**
     * @dataProvider methodsProvider
     */
    public function testOptionsPreflightRequestForToManySubresource(string $requestMethod)
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'staff'],
            [
                'HTTP_Origin'                        => 'https://api.test.com',
                'HTTP_Access-Control-Request-Method' => $requestMethod
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeader($response, 'Access-Control-Allow-Methods', 'OPTIONS, GET');
        self::assertResponseHeader($response, 'Access-Control-Allow-Headers', 'Authorization,Content-Type,X-Include');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeader($response, 'Access-Control-Max-Age', 600);
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
        self::assertResponseHeaderNotExists($response, 'Allow');
    }

    /**
     * @dataProvider methodsProvider
     */
    public function testOptionsPreflightRequestForToOneRelationship(string $requestMethod)
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'owner'],
            [
                'HTTP_Origin'                        => 'https://api.test.com',
                'HTTP_Access-Control-Request-Method' => $requestMethod
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeader($response, 'Access-Control-Allow-Methods', 'OPTIONS, GET, PATCH');
        self::assertResponseHeader($response, 'Access-Control-Allow-Headers', 'Authorization,Content-Type,X-Include');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeader($response, 'Access-Control-Max-Age', 600);
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
        self::assertResponseHeaderNotExists($response, 'Allow');
    }

    /**
     * @dataProvider methodsProvider
     */
    public function testOptionsPreflightRequestForToManyRelationship(string $requestMethod)
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'staff'],
            [
                'HTTP_Origin'                        => 'https://api.test.com',
                'HTTP_Access-Control-Request-Method' => $requestMethod
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeader($response, 'Access-Control-Allow-Methods', 'OPTIONS, GET, PATCH, POST, DELETE');
        self::assertResponseHeader($response, 'Access-Control-Allow-Headers', 'Authorization,Content-Type,X-Include');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeader($response, 'Access-Control-Max-Age', 600);
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
        self::assertResponseHeaderNotExists($response, 'Allow');
    }

    public function testOptionsAsCorsButNotPreflightRequestForList()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => $entityType],
            [
                'HTTP_Origin' => 'https://api.test.com'
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
        self::assertResponseHeader($response, 'Allow', 'OPTIONS, GET, PATCH, POST, DELETE');
    }

    public function testOptionsAsCorsButNotPreflightRequestForItem()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => $entityType, 'id' => '9999'],
            [
                'HTTP_Origin' => 'https://api.test.com'
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
        self::assertResponseHeader($response, 'Allow', 'OPTIONS, GET, PATCH, DELETE');
    }

    public function testOptionsAsCorsButNotPreflightRequestForToOneSubresource()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'owner'],
            [
                'HTTP_Origin' => 'https://api.test.com'
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
        self::assertResponseHeader($response, 'Allow', 'OPTIONS, GET');
    }

    public function testOptionsAsCorsButNotPreflightRequestForToManySubresource()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'staff'],
            [
                'HTTP_Origin' => 'https://api.test.com'
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
        self::assertResponseHeader($response, 'Allow', 'OPTIONS, GET');
    }

    public function testOptionsAsCorsButNotPreflightRequestForToOneRelationship()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'owner'],
            [
                'HTTP_Origin' => 'https://api.test.com'
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
        self::assertResponseHeader($response, 'Allow', 'OPTIONS, GET, PATCH');
    }

    public function testOptionsAsCorsButNotPreflightRequestForToManyRelationship()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'staff'],
            [
                'HTTP_Origin' => 'https://api.test.com'
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
        self::assertResponseHeader($response, 'Allow', 'OPTIONS, GET, PATCH, POST, DELETE');
    }

    public function testOptionsAsNotCorsRequestForList()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => $entityType]
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Origin');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Expose-Headers');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Credentials');
        self::assertResponseHeader($response, 'Allow', 'OPTIONS, GET, PATCH, POST, DELETE');
    }

    public function testOptionsAsNotCorsRequestForItem()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => $entityType, 'id' => '9999']
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Origin');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Expose-Headers');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Credentials');
        self::assertResponseHeader($response, 'Allow', 'OPTIONS, GET, PATCH, DELETE');
    }

    public function testOptionsAsNotCorsRequestForToOneSubresource()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'owner']
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Origin');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Expose-Headers');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Credentials');
        self::assertResponseHeader($response, 'Allow', 'OPTIONS, GET');
    }

    public function testOptionsAsNotCorsRequestForToManySubresource()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'staff']
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Origin');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Expose-Headers');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Credentials');
        self::assertResponseHeader($response, 'Allow', 'OPTIONS, GET');
    }

    public function testOptionsAsNotCorsRequestForToOneRelationship()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'owner']
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Origin');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Expose-Headers');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Credentials');
        self::assertResponseHeader($response, 'Allow', 'OPTIONS, GET, PATCH');
    }

    public function testOptionsAsNotCorsRequestForToManyRelationship()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'staff']
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Origin');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Expose-Headers');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Credentials');
        self::assertResponseHeader($response, 'Allow', 'OPTIONS, GET, PATCH, POST, DELETE');
    }

    public function testCorsRequest()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cget(
            ['entity' => $entityType],
            [],
            [
                'HTTP_Origin' => 'https://api.test.com'
            ]
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
    }

    public function testCorsRequestWithValidationError()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->post(
            ['entity' => $entityType],
            [],
            [
                'HTTP_Origin' => 'https://api.test.com'
            ],
            false
        );
        $this->assertResponseValidationError(
            ['title' => 'request data constraint'],
            $response
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
    }

    public function testCorsRequestWithUnhandledError()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'POST',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType]),
            [],
            [
                'HTTP_Origin' => 'https://api.test.com'
            ],
            '{"data": {"type": test"}}'
        );
        $this->assertResponseValidationError(
            ['title' => 'bad request http exception'],
            $response
        );
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
    }

    public function testCorsRequestWithUnauthenticated()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cget(
            ['entity' => $entityType],
            [],
            [
                'HTTP_Origin' => 'https://api.test.com',
                'HTTP_X-WSSE' => self::generateWsseAuthHeader('NotExistingUser')
            ],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
        self::assertSame('', $response->getContent());
        self::assertResponseHeader($response, 'WWW-Authenticate', 'WSSE realm="Secured API", profile="UsernameToken"');
        self::assertResponseHeader($response, 'Access-Control-Allow-Origin', 'https://api.test.com');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Methods');
        self::assertResponseHeaderNotExists($response, 'Access-Control-Allow-Headers');
        self::assertResponseHeader(
            $response,
            'Access-Control-Expose-Headers',
            'Location,X-Include-Total-Count,X-Include-Deleted-Count'
        );
        self::assertResponseHeaderNotExists($response, 'Access-Control-Max-Age');
        self::assertResponseHeader($response, 'Access-Control-Allow-Credentials', 'true');
    }
}
