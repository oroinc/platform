<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConfigViewCommandTest extends WebTestCase
{
    public function testBoolTrue(): void
    {
        $output = self::runCommand('oro:config:view', ['oro_frontend.web_api']);
        self::assertEquals('true', $output);
    }

    public function testBoolFalse(): void
    {
        $output = self::runCommand('oro:config:view', ['oro_report.display_sql_query']);
        self::assertEquals('false', $output);
    }

    public function testNull(): void
    {
        $output = self::runCommand('oro:config:view', ['oro_shopping_list.default_guest_shopping_list_owner']);
        self::assertEquals('null', $output);
    }

    public function testInt(): void
    {
        $output = self::runCommand('oro:config:view', ['oro_product.new_arrivals_products_segment_id']);
        self::assertEquals('2', $output);
    }

    public function testFloat(): void
    {
        $output = self::runCommand('oro:config:view', ['oro_seo.sitemap_priority_product']);
        self::assertEquals('0.5', $output);
    }

    public function testString(): void
    {
        $output = self::runCommand('oro:config:view', ['oro_pricing_pro.default_currency']);
        self::assertEquals('USD', $output);
    }

    public function testArray(): void
    {
        $output = self::runCommand('oro:config:view', ['oro_shipping.length_units']);
        self::assertEquals('[ "inch", "foot", "cm", "m" ]', $output);
    }

    public function testAssocArray(): void
    {
        $output = self::runCommand('oro:config:view', ['oro_locale.quarter_start']);
        self::assertEquals('{ "month": "1", "day": "1" }', $output);
    }

    public function testEncryptedValue(): void
    {
        $output = self::runCommand('oro:config:view', ['oro_google_integration.client_secret']);
        self::assertEquals('[ERROR] Encrypted value', $output);
    }

    public function testNonexistentField(): void
    {
        $output = self::runCommand('oro:config:view', ['oro_example.nonexistent_field']);
        self::assertEquals('[ERROR] Unknown config field', $output);
    }
}
