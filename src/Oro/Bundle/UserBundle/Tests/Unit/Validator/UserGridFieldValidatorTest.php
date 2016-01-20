<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Validator\UserGridFieldValidator;

class UserGridFieldValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var UserGridFieldValidator */
    protected $validator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->setMethods(['getLoggedUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new UserGridFieldValidator($this->securityFacade);
    }

    /**
     * @dataProvider hasAccessEditFiledDataProvider
     *
     * @param int $currentUserId
     * @param int $userId
     * @param string $fieldName
     * @param bool $result
     */
    public function testHasAccessEditField($currentUserId, $userId, $fieldName, $result)
    {
        $currentUser = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $currentUser->expects(self::once())->method('getId')->willReturn($currentUserId);

        $this->securityFacade->expects(self::once())->method('getLoggedUser')->willReturn($currentUser);

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
                'result'        => false
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
