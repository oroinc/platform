<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\AnnotationHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\RestDocStatusCodesHandler;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;

class RestDocStatusCodesHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RestDocStatusCodesHandler */
    private $statusCodesHandler;

    protected function setUp(): void
    {
        $this->statusCodesHandler = new RestDocStatusCodesHandler();
    }

    private function getAnnotationStatusCodes(ApiDoc $annotation): ?array
    {
        $data = $annotation->toArray();

        return $data['statusCodes'] ?? null;
    }

    public function testHandleWithoutStatusCodes()
    {
        $annotation = new ApiDoc([]);
        $statusCodes = new StatusCodesConfig();

        $this->statusCodesHandler->handle($annotation, $statusCodes);

        self::assertNull($this->getAnnotationStatusCodes($annotation));
    }

    public function testHandle()
    {
        $annotation = new ApiDoc([]);
        $statusCodes = new StatusCodesConfig();
        $statusCodes->addCode('400')->setDescription('Bad Request');
        $statusCodes->addCode('200')->setDescription('OK');
        $statusCodes->addCode('500');
        $statusCodes->addCode('401')->setExcluded();

        $this->statusCodesHandler->handle($annotation, $statusCodes);

        self::assertSame(
            [
                '200' => ['OK'],
                '400' => ['Bad Request'],
                '500' => [null]
            ],
            $this->getAnnotationStatusCodes($annotation)
        );
    }
}
