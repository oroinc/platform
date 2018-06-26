<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Validator\UserGridFieldValidator;

class UserGridFieldValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserGridFieldValidator */
    protected $validator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->validator = new UserGridFieldValidator($this->tokenAccessor);
    }

    /**
     * @dataProvider hasAccessEditFiledDataProvider
     *
     * @param int    $currentUserId
     * @param int    $userId
     * @param string $fieldName
     * @param bool   $result
     */
    public function testHasAccessEditField($currentUserId, $userId, $fieldName, $result)
    {
        $currentUser = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        if ('enabled' === $fieldName) {
            $currentUser->expects(self::once())->method('getId')->willReturn($currentUserId);
        }

        $this->tokenAccessor->expects(self::once())->method('getUser')->willReturn($currentUser);

        $entity = new User();
        $entity->setId($userId);

        self::assertEquals(
            $this->validator->hasAccessEditField($entity, $fieldName),
            $result
        );
    }

    /**
     * @return array
     */
    public function hasAccessEditFiledDataProvider()
    {
        return [
            'field is in black list and user is current user' => [
                'currentUserId' => 1,
                'userId'        => 1,
                'fieldName'     => 'enabled',
                'result'        => false
            ],
            'user is not current user'                        => [
                'currentUserId' => 1,
                'userId'        => 2,
                'fieldName'     => 'enabled',
                'result'        => true
            ],
            'field is not in black list user is current user' => [
                'currentUserId' => 1,
                'userId'        => 1,
                'fieldName'     => 'email',
                'result'        => true
            ],
        ];
    }
}
