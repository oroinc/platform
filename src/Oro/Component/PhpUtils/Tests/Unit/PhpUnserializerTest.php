<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\Exception\UnsafeUnserializationException;
use Oro\Component\PhpUtils\PhpUnserializer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PhpUnserializerTest extends TestCase
{
    /**
     * @dataProvider allowedPayloadsProvider
     */
    public function testCheckSerializedStringDoesNotThrowForAllowedPayloads(string $payload): void
    {
        $this->createUnserializer()->checkSerializedString($payload);
        $this->addToAssertionCount(1);
    }

    public function allowedPayloadsProvider(): array
    {
        return [
            'scalar string' => ['s:5:"hello";'],
            'scalar integer' => ['i:42;'],
            'scalar float' => ['d:3.14;'],
            'scalar boolean true' => ['b:1;'],
            'scalar boolean false' => ['b:0;'],
            'null' => ['N;'],
            'empty array' => ['a:0:{}'],
            'simple indexed array' => ['a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}'],
            'nested array' => ['a:1:{s:5:"inner";a:1:{s:3:"key";s:3:"val";}}'],

            // PHP built-in date/time classes are always permitted
            'DateTime' => ['O:8:"DateTime":0:{}'],
            'DateTimeImmutable' => ['O:17:"DateTimeImmutable":0:{}'],
            'DateTimeZone' => ['O:12:"DateTimeZone":0:{}'],
            'DateInterval' => ['O:12:"DateInterval":0:{}'],
            'DatePeriod' => ['O:10:"DatePeriod":0:{}'],

            // Doctrine collection classes are always permitted
            'Doctrine ArrayCollection' => ['O:43:"Doctrine\Common\Collections\ArrayCollection":0:{}'],
            'Doctrine PersistentCollection' => ['O:33:"Doctrine\ORM\PersistentCollection":0:{}'],

            // Comparisons are case-insensitive
            'DateTime uppercase' => ['O:8:"DATETIME":0:{}'],
            'DateTimeImmutable lowercase' => ['O:17:"datetimeimmutable":0:{}'],

            // Classes in the Oro\ namespace are allowed
            'Oro namespace class' => ['O:31:"Oro\Bundle\SomeBundle\SomeClass":0:{}'],
            'Oro class lowercase prefix' => ['O:31:"oro\Bundle\SomeBundle\SomeClass":0:{}'],
            'Oro class with leading backslash' => ['O:32:"\Oro\Bundle\SomeBundle\SomeClass":0:{}'],

            // Multiple allowed objects in one payload
            'two Oro namespace classes' => [
                'a:2:{i:0;O:29:"Oro\Bundle\SomeBundle\EntityA":0:{}i:1;O:29:"Oro\Bundle\SomeBundle\EntityB":0:{}}',
            ],
            'Oro class and DateTime mixed' => [
                'a:2:{i:0;O:8:"DateTime":0:{}i:1;O:31:"Oro\Bundle\SomeBundle\SomeClass":0:{}}',
            ],

            // Private/protected property names contain null bytes; they must be stripped before checking
            'Oro class with null bytes in property' => [
                "O:31:\"Oro\\Bundle\\SomeBundle\\SomeClass\":1:{s:10:\"\0*\0propName\";s:3:\"val\";}",
            ],

            // ---------------------------------------------------------------------------
            // + sign prefix on object-size (phpggc --plus-numbers O)
            // The regex uses \+* so it absorbs the leading + and still extracts the
            // class name correctly. Oro classes with a + size must still be allowed.
            // ---------------------------------------------------------------------------
            'Oro class with + prefix on object size (--plus-numbers O)' => [
                'O:+31:"Oro\Bundle\SomeBundle\SomeClass":0:{}',
            ],
            'Oro class with leading backslash and + prefix on size' => [
                'O:+32:"\Oro\Bundle\SomeBundle\SomeClass":0:{}',
            ],
            'DateTime with + prefix on object size' => [
                'O:+8:"DateTime":0:{}',
            ],

            // ---------------------------------------------------------------------------
            // Doctrine proxy class prefix stripping (new behaviour introduced in BAP-23335)
            // 'Proxies\__CG__\<ClassName>' has the prefix stripped before the org/core check,
            // so a proxy for an Oro entity must be allowed.
            // ---------------------------------------------------------------------------
            'Doctrine proxy of an Oro entity' => [
                'O:40:"Proxies\__CG__\Oro\Bundle\Foo\Entity\Bar":0:{}',
            ],
            'Doctrine proxy of an Oro entity – uppercase proxy prefix' => [
                'O:40:"PROXIES\__CG__\Oro\Bundle\Foo\Entity\Bar":0:{}',
            ],
            'Doctrine proxy of a core class (DateTime)' => [
                'O:22:"Proxies\__CG__\DateTime":0:{}',
            ],
            'Doctrine proxy with leading backslash before proxy prefix' => [
                // ltrim strips the leading '\', then str_replace removes 'proxies\__cg__\'
                'O:41:"\Proxies\__CG__\Oro\Bundle\Foo\Entity\Bar":0:{}',
            ],

            // ---------------------------------------------------------------------------
            // Fake serialization tokens embedded in string values
            // These look like object headers but do not contain a reachable class name.
            // ---------------------------------------------------------------------------
            // "O:4:" inside a string key ends with the string's closing " then ;
            // The char after the final " is ; which does not match [a-zA-Z_\\] → no match.
            'string key with fake O: token – no valid class char follows the quote' => [
                'a:1:{s:9:"fake O:4:";i:1;}',
            ],
            // "C:9:" pattern inside a string value is not followed by " → no match.
            'string value with fake C: token – digit length not followed by quote' => [
                's:14:"hello C:9:nope;"',
            ],
        ];
    }

    /**
     * @dataProvider dangerousPayloadsProvider
     */
    public function testCheckSerializedStringThrowsForDangerousPayloads(
        string $payload,
        string $expectedClassFragment
    ): void {
        $this->expectException(UnsafeUnserializationException::class);
        $this->expectExceptionMessage($expectedClassFragment);

        $this->createUnserializer()->checkSerializedString($payload);
    }

    public function dangerousPayloadsProvider(): array
    {
        return [
            'unknown vendor namespace' => [
                'O:20:"Vendor\SomeNamespace":0:{}',
                'vendor\somenamespace',
            ],
            'non-namespaced class' => [
                'O:9:"SomeClass":0:{}',
                'someclass',
            ],

            // Classes inside a \Tests\ sub-namespace are always blocked, even under Oro\
            'Oro class under Tests namespace' => [
                'O:46:"Oro\Bundle\SomeBundle\Tests\Unit\SomeTestClass":0:{}',
                'oro\bundle\somebundle\tests\unit\sometestclass',
            ],

            // Default deny-list
            'GaufretteBundle FileManager' => [
                'O:38:"Oro\Bundle\GaufretteBundle\FileManager":0:{}',
                'oro\bundle\gaufrettebundle\filemanager',
            ],
            'AttachmentBundle TemporaryFile' => [
                'O:54:"Oro\Bundle\AttachmentBundle\Manager\File\TemporaryFile":0:{}',
                'oro\bundle\attachmentbundle\manager\file\temporaryfile',
            ],
            'PdfGeneratorBundle PdfFile' => [
                'O:45:"Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFile":0:{}',
                'oro\bundle\pdfgeneratorbundle\pdffile\pdffile',
            ],
            'DotmailerBundle CsvStringReader' => [
                'O:51:"Oro\Bundle\DotmailerBundle\Provider\CsvStringReader":0:{}',
                'oro\bundle\dotmailerbundle\provider\csvstringreader',
            ],
            'BatchBundle BatchLogHandler' => [
                'O:54:"Oro\Bundle\BatchBundle\Monolog\Handler\BatchLogHandler":0:{}',
                'oro\bundle\batchbundle\monolog\handler\batchloghandler',
            ],
            'MaintenanceBundle FileDriver' => [
                'O:47:"Oro\Bundle\MaintenanceBundle\Drivers\FileDriver":0:{}',
                'oro\bundle\maintenancebundle\drivers\filedriver',
            ],

            // A safe class alongside a dangerous class in the same payload
            'dangerous class mixed with allowed Oro class' => [
                'a:2:{i:0;O:31:"Oro\Bundle\SomeBundle\SomeClass":0:{}i:1;O:9:"SomeClass":0:{}}',
                'someclass',
            ],

            // Doctrine proxy prefix stripping: a proxy wrapping a disallowed (non-Oro) class
            'Doctrine proxy of a disallowed (non-namespaced) class' => [
                'O:28:"Proxies\__CG__\stdClass":0:{}',
                'stdclass',
            ],
            'Doctrine proxy of an unknown vendor class' => [
                'O:42:"Proxies\__CG__\Vendor\Bundle\SomeClass":0:{}',
                'vendor\bundle\someclass',
            ],

            // ---------------------------------------------------------------------------
            // + sign on object-size numbers (phpggc --plus-numbers O / -n O)
            // The regex uses \+* to absorb the optional leading +, so these must be
            // caught regardless of the + prefix.
            // ---------------------------------------------------------------------------
            'plus sign on dangerous object size (--plus-numbers O)' => [
                'O:+32:"Monolog\Handler\SyslogUdpHandler":0:{}',
                'monolog\handler\syslogudphandler',
            ],
            'plus sign on both objects in nested payload' => [
                'O:+32:"Monolog\Handler\SyslogUdpHandler":1:{s:6:"socket";O:+29:"Monolog\Handler\BufferHandler":0:{}}',
                'monolog\handler\syslogudphandler',
            ],

            // ---------------------------------------------------------------------------
            // --fast-destruct wrapping (phpggc -f)
            // Wraps the payload in a:2:{i:7;O:...; i:7;i:7;} so that __destruct fires
            // immediately after unserialize(). The object is still in O: format.
            // ---------------------------------------------------------------------------
            'fast-destruct array wrapping around dangerous object' => [
                'a:2:{i:7;O:32:"Monolog\Handler\SyslogUdpHandler":0:{};i:7;i:7;}',
                'monolog\handler\syslogudphandler',
            ],

            // ---------------------------------------------------------------------------
            // --ascii-strings / --armor-strings (phpggc -a / -A)
            // These options change string *values* from s: to S: (with hex escapes for
            // non-ASCII or all chars). The O: class-name declarations are unaffected.
            // ---------------------------------------------------------------------------
            'ascii-strings variant: S: for string values, O: for class names' => [
                'O:32:"Monolog\Handler\SyslogUdpHandler":1:{S:6:"socket";O:29:"Monolog\Handler\BufferHandler":0:{}}',
                'monolog\handler\syslogudphandler',
            ],
            'armor-strings variant: all chars hex-encoded inside S: strings' => [
                'O:32:"Monolog\Handler\SyslogUdpHandler":1:{S:6:"\73\6f\63\6b\65\74";'
                . 'O:29:"Monolog\Handler\BufferHandler":0:{}}',
                'monolog\handler\syslogudphandler',
            ],

            // ---------------------------------------------------------------------------
            // C: custom-serialize type — for classes implementing Serializable.
            // The [CEO] character class in the regex covers C:, so these are caught.
            // ---------------------------------------------------------------------------
            'custom-serialize C: type with dangerous class' => [
                'C:40:"Swift_ByteStream_TemporaryFileByteStream":5:{hello}',
                'swift_bytestream_temporaryfilebytestream',
            ],

            // ---------------------------------------------------------------------------
            // Regex bypass attempts: fake O: tokens mixed with real dangerous objects
            // ---------------------------------------------------------------------------
            // The fake "O:4:" inside a string key ends at the closing " then ;,
            // so the regex does NOT match it. The real O:9:"SomeClass" IS matched.
            'fake O: string key beside a real dangerous object' => [
                'a:1:{s:9:"fake O:4:";O:9:"SomeClass":0:{}}',
                'someclass',
            ],
            'dangerous class deeply nested inside plain arrays' => [
                'a:1:{i:0;a:1:{i:0;a:1:{i:0;O:9:"SomeClass":0:{}}}}',
                'someclass',
            ],
            'dangerous class used as an array key' => [
                'a:1:{O:9:"SomeClass":0:{};s:3:"val";}',
                'someclass',
            ],

            // ---------------------------------------------------------------------------
            // Real-world phpggc gadget chains (https://github.com/ambionics/phpggc)
            // All use classes outside the Oro\ namespace → must be blocked.
            // Payloads generated with --public-properties (no null bytes) for clarity.
            // ---------------------------------------------------------------------------

            // Monolog/RCE1 – SyslogUdpHandler → BufferHandler chain (RCE via system())
            // phpggc Monolog/RCE1 system id -pub
            'phpggc Monolog/RCE1 plain (RCE)' => [
                'O:32:"Monolog\Handler\SyslogUdpHandler":1:{s:6:"socket";O:29:"Monolog\Handler\BufferHandler":7:{'
                . 's:7:"handler";r:2;s:10:"bufferSize";i:-1;s:6:"buffer";a:1:{i:0;a:2:{i:0;s:2:"id";s:5:"level";N;}}'
                . 's:5:"level";N;s:11:"initialized";b:1;s:11:"bufferLimit";i:-1;s:10:"processors";a:2:{i:0;s:7:'
                . '"current";i:1;s:6:"system";}}}',
                'monolog\handler\syslogudphandler',
            ],
            // phpggc Monolog/RCE1 system id -n O -pub   (--plus-numbers O)
            'phpggc Monolog/RCE1 --plus-numbers O (RCE, + on every O size)' => [
                'O:+32:"Monolog\Handler\SyslogUdpHandler":1:{s:6:"socket";O:+29:"Monolog\Handler\BufferHandler":7:{'
                . 's:7:"handler";r:2;s:10:"bufferSize";i:-1;s:6:"buffer";a:1:{i:0;a:2:{i:0;s:2:"id";s:5:"level";N;}}'
                . 's:5:"level";N;s:11:"initialized";b:1;s:11:"bufferLimit";i:-1;s:10:"processors";a:2:{i:0;s:7:'
                . '"current";i:1;s:6:"system";}}}',
                'monolog\handler\syslogudphandler',
            ],
            // phpggc Monolog/RCE1 system id -f -pub   (--fast-destruct)
            'phpggc Monolog/RCE1 --fast-destruct (immediate __destruct trigger)' => [
                'a:2:{i:7;O:32:"Monolog\Handler\SyslogUdpHandler":1:{s:6:"socket";O:29:"Monolog\Handler\BufferHandler":'
                . '7:{s:7:"handler";r:3;s:10:"bufferSize";i:-1;s:6:"buffer";a:1:{i:0;a:2:{i:0;s:2:"id";s:5:"level";N;}}'
                . 's:5:"level";N;s:11:"initialized";b:1;s:11:"bufferLimit";i:-1;s:10:"processors";a:2:{i:0;s:7:'
                . '"current";i:1;s:6:"system";}}}i:7;i:7;}',
                'monolog\handler\syslogudphandler',
            ],
            // phpggc Monolog/RCE1 system id -a -pub   (--ascii-strings)
            'phpggc Monolog/RCE1 --ascii-strings (S: type for string values)' => [
                'O:32:"Monolog\Handler\SyslogUdpHandler":1:{S:6:"socket";O:29:"Monolog\Handler\BufferHandler":7:{'
                . 'S:7:"handler";r:2;S:10:"bufferSize";i:-1;S:6:"buffer";a:1:{i:0;a:2:{i:0;S:2:"id";S:5:"level";N;}}'
                . 'S:5:"level";N;S:11:"initialized";b:1;S:11:"bufferLimit";i:-1;S:10:"processors";a:2:{i:0;S:7:'
                . '"current";i:1;S:6:"system";}}}',
                'monolog\handler\syslogudphandler',
            ],
            'phpggc Monolog/RCE1 --armor-strings (all chars hex-encoded in S:)' => [
                'O:32:"Monolog\Handler\SyslogUdpHandler":1:{S:6:"\73\6f\63\6b\65\74";'
                . 'O:29:"Monolog\Handler\BufferHandler":7:{S:7:"\68\61\6e\64\6c\65\72";r:2;'
                . 'S:10:"\62\75\66\66\65\72\53\69\7a\65";i:-1;S:6:"\62\75\66\66\65\72";'
                . 'a:1:{i:0;a:2:{i:0;S:2:"\69\64";S:5:"\6c\65\76\65\6c";N;}}S:5:"\6c\65\76\65\6c";N;'
                . 'S:11:"\69\6e\69\74\69\61\6c\69\7a\65\64";b:1;S:11:"\62\75\66\66\65\72\4c\69\6d\69\74";'
                . 'i:-1;S:10:"\70\72\6f\63\65\73\73\6f\72\73";a:2:{i:0;S:7:"\63\75\72\72\65\6e\74";'
                . 'i:1;S:6:"\73\79\73\74\65\6d";}}}',
                'monolog\handler\syslogudphandler',
            ],

            // phpggc Guzzle/FW1 (file write)
            'phpggc Guzzle/FW1 FileCookieJar → SetCookie (file write)' => [
                'O:31:"GuzzleHttp\Cookie\FileCookieJar":4:{s:7:"cookies";a:1:{i:0;O:27:"GuzzleHttp\Cookie\SetCookie":'
                . '1:{s:4:"data";a:3:{s:7:"Expires";i:1;s:7:"Discard";b:0;s:5:"Value";s:4:"test";}}}s:10:"strictMode";'
                . 'N;s:8:"filename";s:12:"/tmp/out.txt";s:19:"storeSessionCookies";b:1;}',
                'guzzlehttp\cookie\filecookiejar',
            ],

            // phpggc Symfony/RCE4 (RCE via TagAwareAdapter chain)
            'phpggc Symfony/RCE4 TagAwareAdapter chain (RCE)' => [
                'O:47:"Symfony\Component\Cache\Adapter\TagAwareAdapter":2:{s:8:"deferred";a:1:{i:0;'
                . 'O:33:"Symfony\Component\Cache\CacheItem":2:{s:8:"poolHash";i:1;s:9:"innerItem";s:2:"id";}}'
                . '}s:4:"pool";O:44:"Symfony\Component\Cache\Adapter\ProxyAdapter":2:{s:8:"poolHash";i:1;'
                . 's:12:"setInnerItem";s:6:"system";}}',
                'symfony\component\cache\adapter\tagawareadapter',
            ],

            // phpggc Laminas/FD1 (file delete)
            'phpggc Laminas/FD1 Response\\Stream (file delete)' => [
                'O:28:"Laminas\Http\Response\Stream":2:{s:7:"cleanup";s:1:"1";s:10:"streamName";s:13:"/tmp/test.txt";}',
                'laminas\http\response\stream',
            ],

            // phpggc SwiftMailer/FD1 (file delete)
            'phpggc SwiftMailer/FD1 TemporaryFileByteStream (file delete)' => [
                'O:40:"Swift_ByteStream_TemporaryFileByteStream":1:{s:4:"path";s:13:"/tmp/test.txt";}',
                'swift_bytestream_temporaryfilebytestream',
            ],
        ];
    }

    /**
     * @dataProvider unserializeAllowedPayloadsProvider
     */
    public function testUnserializeReturnsCorrectValueForAllowedPayloads(
        string $payload,
        mixed $expected
    ): void {
        self::assertEquals($expected, $this->createUnserializer()->unserialize($payload));
    }

    public function unserializeAllowedPayloadsProvider(): array
    {
        return [
            'string' => ['s:5:"hello";', 'hello'],
            'integer' => ['i:42;', 42],
            'float' => ['d:3.14;', 3.14],
            'null' => ['N;', null],
            'boolean true' => ['b:1;', true],
            'boolean false' => ['b:0;', false],
            'empty array' => ['a:0:{}', []],
            'indexed array' => ['a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}', ['foo', 'bar']],
        ];
    }

    /**
     * @dataProvider dangerousPayloadsProvider
     */
    public function testUnserializeThrowsForDangerousPayloadsWhenBlockOnFailureIsTrue(
        string $payload,
        string $expectedClassFragment
    ): void {
        $this->expectException(UnsafeUnserializationException::class);
        $this->expectExceptionMessage($expectedClassFragment);

        $this->createUnserializer(blockOnFailure: true)->unserialize($payload);
    }

    public function testUnserializeSkipsSecurityCheckWhenAllowedClassesIsFalse(): void
    {
        // allowed_classes => false tells PHP to return __PHP_Incomplete_Class instead of real objects;
        // the security check must be bypassed entirely in this case.
        $result = $this->createUnserializer()->unserialize('O:9:"SomeClass":0:{}', ['allowed_classes' => false]);

        self::assertIsObject($result);
    }

    public function testUnserializeSkipsSecurityCheckWhenAllowedClassesIsAnArray(): void
    {
        // When the caller supplies an explicit allow-list PHP handles class filtering itself;
        // PhpUnserializer must not run its own check in this case.
        $result = $this->createUnserializer()
            ->unserialize('O:9:"SomeClass":0:{}', ['allowed_classes' => ['SomeClass']]);

        self::assertIsObject($result);
    }

    public function testUnserializeRunsSecurityCheckWhenAllowedClassesIsTrue(): void
    {
        $this->expectException(UnsafeUnserializationException::class);

        $this->createUnserializer(blockOnFailure: true)
            ->unserialize('O:9:"SomeClass":0:{}', ['allowed_classes' => true]);
    }

    public function testUnserializeLogsAndDoesNotThrowForDangerousPayloadByDefault(): void
    {
        // blockOnFailure=false (default): the check failure is only logged; unserialize proceeds.
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with(
                'Unserialization failed',
                $this->callback(
                    fn (array $context) => $context['exception'] instanceof UnsafeUnserializationException
                )
            );

        // Must not throw
        $this->createUnserializer($logger, blockOnFailure: false)->unserialize('O:9:"SomeClass":0:{}');
    }

    public function testUnserializeLogsAndThrowsForDangerousPayloadWhenBlockOnFailureIsTrue(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with(
                'Unserialization failed',
                $this->callback(
                    fn (array $context) => $context['exception'] instanceof UnsafeUnserializationException
                )
            );

        $this->expectException(UnsafeUnserializationException::class);

        $this->createUnserializer($logger, blockOnFailure: true)->unserialize('O:9:"SomeClass":0:{}');
    }

    public function testCustomTrustedOrgIsAllowed(): void
    {
        // A class from the newly trusted Acme\ namespace must pass the check.
        $this->createUnserializer(trustedOrgs: ['Acme\\'])
            ->checkSerializedString('O:21:"Acme\Bundle\SomeClass":0:{}');
        $this->addToAssertionCount(1);
    }

    public function testCustomTrustedOrgDoesNotAllowTestClasses(): void
    {
        // Even within a trusted org, classes inside a \Tests\ namespace are blocked.
        $this->expectException(UnsafeUnserializationException::class);
        $this->expectExceptionMessage('acme\bundle\tests\unit\someclass');

        $this->createUnserializer(trustedOrgs: ['Acme\\'])
            ->checkSerializedString('O:32:"Acme\Bundle\Tests\Unit\SomeClass":0:{}');
    }

    public function testCustomNotAllowedClassIsBlocked(): void
    {
        $this->expectException(UnsafeUnserializationException::class);
        $this->expectExceptionMessage('oro\bundle\somebundle\dangerousclass');

        $this->createUnserializer(notAllowedClasses: ['Oro\Bundle\SomeBundle\DangerousClass'])
            ->checkSerializedString('O:36:"Oro\Bundle\SomeBundle\DangerousClass":0:{}');
    }

    public function testMultipleCustomNotAllowedClassesAreBlocked(): void
    {
        $this->expectException(UnsafeUnserializationException::class);
        $this->expectExceptionMessage('oro\bundle\otherbundle\anotherdangerousclass');

        $this->createUnserializer(notAllowedClasses: [
            'Oro\Bundle\SomeBundle\DangerousClass',
            'Oro\Bundle\OtherBundle\AnotherDangerousClass',
        ])->checkSerializedString(
            'O:44:"Oro\Bundle\OtherBundle\AnotherDangerousClass":0:{}'
        );
    }

    public function testCheckSerializedStringAllowsClassProvidedInWhitelist(): void
    {
        $this->createUnserializer()->checkSerializedString(serialize(new \stdClass()), [\stdClass::class]);
        $this->addToAssertionCount(1);
    }

    public function testCheckSerializedStringWhitelistDoesNotAffectBlockedClasses(): void
    {
        // A class that is in DEFAULT_NOT_ALLOWED_CLASSES is rejected even when whitelisted,
        // because the deny-check runs before the whitelist diff.
        $this->expectException(UnsafeUnserializationException::class);
        $this->expectExceptionMessage('oro\bundle\gaufrettebundle\filemanager');

        $this->createUnserializer()->checkSerializedString(
            'O:38:"Oro\Bundle\GaufretteBundle\FileManager":0:{}',
            ['Oro\Bundle\GaufretteBundle\FileManager']
        );
    }

    /**
     * @dataProvider unserializeWithWhitelistKeyProvider
     */
    public function testUnserializeWithWhitelistKey(
        string $payload,
        array $whitelist,
        mixed $expectedValue
    ): void {
        $result = $this->createUnserializer()->unserialize($payload, [
            PhpUnserializer::WHITELIST_CLASSES_KEY => $whitelist,
        ]);
        $this->assertEquals($expectedValue, $result);
    }

    public function unserializeWithWhitelistKeyProvider(): array
    {
        $dt = new \DateTime('2011-11-11', new \DateTimeZone('UTC'));

        return [
            // WHITELIST_CLASSES_KEY enables a class that would otherwise be blocked
            'stdClass enabled by whitelist' => [
                serialize(new \stdClass()),
                [\stdClass::class],
                new \stdClass(),
            ],
            // WHITELIST_CLASSES_KEY is stripped before PHP's unserialize() receives $options,
            // so the result is a properly typed object
            'whitelist key stripped before PHP unserialize – DateTime returned correctly' => [
                serialize($dt),
                [\DateTime::class],
                $dt,
            ],
        ];
    }

    public function testUnserializeWithWhitelistKeySkipsSecurityCheckWhenAllowedClassesIsFalse(): void
    {
        // When allowed_classes=false the security check is skipped entirely;
        // the WHITELIST_CLASSES_KEY must be ignored in this path too.
        $result = $this->createUnserializer()->unserialize(
            serialize(new \stdClass()),
            ['allowed_classes' => false, PhpUnserializer::WHITELIST_CLASSES_KEY => ['stdclass']]
        );
        $this->assertIsObject($result);
        // PHP returns __PHP_Incomplete_Class when allowed_classes=false, not the real class
        $this->assertNotInstanceOf(\stdClass::class, $result);
    }

    private function createUnserializer(
        ?LoggerInterface $logger = null,
        array $trustedOrgs = [],
        array $notAllowedClasses = [],
        bool $blockOnFailure = false
    ): PhpUnserializer {
        return new PhpUnserializer(
            $logger ?? $this->createMock(LoggerInterface::class),
            $trustedOrgs,
            $notAllowedClasses,
            $blockOnFailure
        );
    }
}
