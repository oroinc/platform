<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Provider\EmailBodyEntityNameProvider;

class EmailBodyEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailBodyEntityNameProvider */
    protected $emailBodyEntityNameProvider;

    protected function setUp()
    {
        $this->emailBodyEntityNameProvider = new EmailBodyEntityNameProvider();
    }

    /**
     * @dataProvider getNameProvider
     */
    public function testGetName($entity, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->emailBodyEntityNameProvider->getName(null, null, $entity));
    }

    public function getNameProvider()
    {
        return [
            'text body' => [
                (new EmailBody())
                    ->setBodyIsText(true)
                    ->setTextBody('text body')
                    ->setBodyContent('body content'),
                'text body'
            ],
            'non text body' => [
                (new EmailBody())
                    ->setBodyIsText(false)
                    ->setTextBody('text body')
                    ->setBodyContent('body content'),
                'body content'
            ],
            'null' => [
                null,
                false,
            ],
            'different entity' => [
                new Email(),
                false,
            ],
        ];
    }

    /**
     * @dataProvider getNameDQLProvider
     */
    public function testGetNameDQL($className, $expectedResult)
    {
        $this->assertSame(
            $expectedResult,
            $this->emailBodyEntityNameProvider->getNameDQL(null, null, $className, 'alias')
        );
    }

    public function getNameDQLProvider()
    {
        return [
            'email body' => [
                EmailBody::class,
                'CASE WHEN alias.bodyIsText = true THEN alias.textBody ELSE alias.bodyContent END',
            ],
            'different entity' => [
                Email::class,
                false,
            ],
        ];
    }
}
