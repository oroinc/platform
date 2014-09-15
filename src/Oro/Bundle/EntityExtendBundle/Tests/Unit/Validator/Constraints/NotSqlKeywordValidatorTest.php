<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Doctrine\DBAL\Platforms\Keywords\MySQLKeywords;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotSqlKeyword;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotSqlKeywordValidator;

class NotSqlKeywordValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var NotSqlKeywordValidator */
    protected $validator;

    protected function setUp()
    {
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $platform = new MySqlPlatform();

        $doctrine->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($platform));

        $this->validator = new NotSqlKeywordValidator($doctrine);
        $this->validator->initialize($this->context);
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate($value, $violation)
    {
        $constraint = new NotSqlKeyword();

        if ($violation) {
            $this->context->expects($this->once())
                ->method('addViolation')
                ->with($constraint->message);
        } else {
            $this->context->expects($this->never())
                ->method('addViolation');
        }

        $this->validator->validate($value, $constraint);
    }

    public function validateDataProvider()
    {
        return [
            ['', false],
            ['test', false],
            ['select', true],
            ['SELECT', true],
        ];
    }
}
