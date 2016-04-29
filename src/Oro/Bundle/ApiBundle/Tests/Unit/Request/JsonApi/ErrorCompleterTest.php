<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\JsonApi\JsonApiDocument;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\JsonApi\ErrorCompleter;

class ErrorCompleterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $exceptionTextExtractor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadata;

    /** @var ErrorCompleter */
    protected $errorCompleter;

    protected function setUp()
    {
        $this->exceptionTextExtractor = $this->getMock('Oro\Bundle\ApiBundle\Request\ExceptionTextExtractorInterface');

        $this->metadata = $this->getMockBuilder('Oro\Bundle\ApiBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->errorCompleter = new ErrorCompleter($this->exceptionTextExtractor);

        $this->metadata = new EntityMetadata();
        $this->metadata->setIdentifierFieldNames(['id']);
        $idField = new FieldMetadata();
        $idField->setName('id');
        $this->metadata->addField($idField);
        $firstNameField = new FieldMetadata();
        $firstNameField->setName('firstName');
        $this->metadata->addField($firstNameField);
        $userAssociation = new AssociationMetadata();
        $userAssociation->setName('user');
        $this->metadata->addAssociation($userAssociation);
        $groupsAssociation = new AssociationMetadata();
        $groupsAssociation->setName('groups');
        $groupsAssociation->setIsCollection(true);
        $this->metadata->addAssociation($groupsAssociation);
    }

    public function testCompleteErrorWithoutInnerException()
    {
        $error = new Error();
        $expectedError = new Error();

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithInnerExceptionAndAlreadyCompletedProperties()
    {
        $exception = new \Exception('some exception');

        $error = new Error();
        $error->setStatusCode(400);
        $error->setCode('test code');
        $error->setTitle('test title');
        $error->setDetail('test detail');
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setStatusCode(400);
        $expectedError->setCode('test code');
        $expectedError->setTitle('test title');
        $expectedError->setDetail('test detail');
        $expectedError->setInnerException($exception);

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithInnerExceptionAndExceptionTextExtractorReturnsNothing()
    {
        $exception = new \Exception('some exception');

        $error = new Error();
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setInnerException($exception);

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithInnerException()
    {
        $exception = new \Exception('some exception');

        $error = new Error();
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setStatusCode(500);
        $expectedError->setCode('test code');
        $expectedError->setTitle('test title');
        $expectedError->setDetail('test detail');
        $expectedError->setInnerException($exception);

        $this->exceptionTextExtractor->expects($this->once())
            ->method('getExceptionStatusCode')
            ->with($this->identicalTo($exception))
            ->willReturn($expectedError->getStatusCode());
        $this->exceptionTextExtractor->expects($this->once())
            ->method('getExceptionCode')
            ->with($this->identicalTo($exception))
            ->willReturn($expectedError->getCode());
        $this->exceptionTextExtractor->expects($this->once())
            ->method('getExceptionType')
            ->with($this->identicalTo($exception))
            ->willReturn($expectedError->getTitle());
        $this->exceptionTextExtractor->expects($this->once())
            ->method('getExceptionText')
            ->with($this->identicalTo($exception))
            ->willReturn($expectedError->getDetail());

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function testCompleteErrorTitleByStatusCode()
    {
        $error = new Error();
        $error->setStatusCode(400);

        $expectedError = new Error();
        $expectedError->setStatusCode(400);
        $expectedError->setTitle(Response::$statusTexts[400]);

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function testCompleteErrorTitleByUnknownStatusCode()
    {
        $error = new Error();
        $error->setStatusCode(1000);

        $expectedError = new Error();
        $expectedError->setStatusCode(1000);

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithPropertyPathAndPointer()
    {
        $error = new Error();
        $errorSource = new ErrorSource();
        $errorSource->setPropertyPath('property');
        $errorSource->setPointer('pointer');
        $error->setDetail('test detail');
        $error->setSource($errorSource);

        $expectedError = new Error();
        $expectedErrorSource = new ErrorSource();
        $expectedErrorSource->setPropertyPath('property');
        $expectedErrorSource->setPointer('pointer');
        $expectedError->setDetail('test detail');
        $expectedError->setSource($expectedErrorSource);

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithPropertyPathAndDetailEndsWithPoint()
    {
        $error = new Error();
        $error->setDetail('test detail.');
        $error->setSource(ErrorSource::createByPropertyPath('property'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail. Source: property.');

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    /**
     * @dataProvider completeErrorWithPropertyPathButWithoutMetadataDataProvider
     */
    public function testCompleteErrorWithPropertyPathButWithoutMetadata($property, $expectedResult)
    {
        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath($property));

        $expectedError = new Error();
        if (array_key_exists('detail', $expectedResult)) {
            $expectedError->setDetail($expectedResult['detail']);
        }

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function completeErrorWithPropertyPathButWithoutMetadataDataProvider()
    {
        return [
            [
                'id',
                [
                    'detail' => 'test detail. Source: id.',
                ]
            ],
            [
                'firstName',
                [
                    'detail' => 'test detail. Source: firstName.',
                ]
            ],
            [
                'user',
                [
                    'detail' => 'test detail. Source: user.',
                ]
            ],
            [
                'nonMappedPointer',
                [
                    'detail' => 'test detail. Source: nonMappedPointer.'
                ]
            ]
        ];
    }

    /**
     * @dataProvider completeErrorWithPropertyPathDataProvider
     */
    public function testCompleteErrorWithPropertyPath($property, $expectedResult)
    {
        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath($property));

        $expectedError = new Error();
        if (array_key_exists('detail', $expectedResult)) {
            $expectedError->setDetail($expectedResult['detail']);
        }
        if (array_key_exists('source', $expectedResult)) {
            $expectedSource = $expectedResult['source'];
            $expectedError->setSource(new ErrorSource());
            if (array_key_exists('pointer', $expectedSource)) {
                $expectedError->getSource()->setPointer($expectedSource['pointer']);
            } elseif (array_key_exists('property', $expectedSource)) {
                $expectedError->getSource()->setPropertyPath($expectedSource['property']);
            }
        }

        $this->errorCompleter->complete($error, $this->metadata);
        $this->assertEquals($expectedError, $error);
    }

    public function completeErrorWithPropertyPathDataProvider()
    {
        return [
            [
                'id',
                [
                    'detail' => 'test detail',
                    'source' => ['pointer' => '/data/id']
                ]
            ],
            [
                'firstName',
                [
                    'detail' => 'test detail',
                    'source' => ['pointer' => '/data/attributes/firstName']
                ]
            ],
            [
                'user',
                [
                    'detail' => 'test detail',
                    'source' => ['pointer' => '/data/relationships/user/data']
                ]
            ],
            [
                'groups',
                [
                    'detail' => 'test detail',
                    'source' => ['pointer' => '/data/relationships/groups/data']
                ]
            ],
            [
                'groups.2',
                [
                    'detail' => 'test detail',
                    'source' => ['pointer' => '/data/relationships/groups/data/2']
                ]
            ],
            [
                'nonMappedPointer',
                [
                    'detail' => 'test detail. Source: nonMappedPointer.'
                ]
            ]
        ];
    }
}
