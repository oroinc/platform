<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\DataMapper;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EmailBundle\Form\DataMapper\EmailTemplateDataMapper;
use Oro\Bundle\EmailBundle\Form\DataMapper\EmailTemplateDataMapperFactory;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateTranslationResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\DataMapperInterface;

final class EmailTemplateDataMapperFactoryTest extends TestCase
{
    public function testCreateDataMapperWithValidInnerDataMapper(): void
    {
        $emailTemplateTranslationResolver = $this->createMock(EmailTemplateTranslationResolver::class);
        $fileManager = $this->createMock(FileManager::class);
        $innerDataMapper = $this->createMock(DataMapperInterface::class);

        $factory = new EmailTemplateDataMapperFactory(
            $emailTemplateTranslationResolver,
            $fileManager
        );
        $dataMapper = $factory->createDataMapper($innerDataMapper);

        self::assertInstanceOf(EmailTemplateDataMapper::class, $dataMapper);
    }

    public function testCreateDataMapperWithNullInnerDataMapper(): void
    {
        $emailTemplateTranslationResolver = $this->createMock(EmailTemplateTranslationResolver::class);
        $fileManager = $this->createMock(FileManager::class);

        $factory = new EmailTemplateDataMapperFactory(
            $emailTemplateTranslationResolver,
            $fileManager
        );
        $dataMapper = $factory->createDataMapper(null);

        self::assertInstanceOf(EmailTemplateDataMapper::class, $dataMapper);
    }
}
