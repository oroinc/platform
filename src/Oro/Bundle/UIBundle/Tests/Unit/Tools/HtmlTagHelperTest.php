<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Filesystem\Filesystem;

class HtmlTagHelperTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var HtmlTagHelper */
    protected $helper;

    /** @var HtmlTagProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $htmlTagProvider;

    /** @var string */
    private $cachePath;

    protected function setUp()
    {
        $this->cachePath = $this->getTempDir('cache_test_data');
        $this->htmlTagProvider = $this->createMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');
        $this->helper = new HtmlTagHelper($this->htmlTagProvider, $this->cachePath);
    }

    protected function tearDown()
    {
        $fileSystem = new Filesystem();
        $fileSystem->remove($this->cachePath);
    }

    public function testGetStripped()
    {
        $actualString = '<div class="new">test1 test2</div><div class="new">test3   test4</div>';
        $expectedString = 'test1 test2 test3 test4';

        $this->assertEquals($expectedString, $this->helper->stripTags($actualString));
    }

    /**
     * @dataProvider shortStringProvider
     *
     * @param string $expected
     * @param string $actual
     * @param int $maxLength
     */
    public function testGetShort($expected, $actual, $maxLength)
    {
        $shortBody = $this->helper->shorten($actual, $maxLength);
        $this->assertEquals($expected, $shortBody);
    }

    /**
     * @return array
     */
    public static function shortStringProvider()
    {
        return [
            ['абв абв абв', 'абв абв абв абв ', 12],
            ['abc abc abc', 'abc abc abc abc ', 12],
            ['abc abc', 'abc abc abc abc abc', 8],
            ['abcab', 'abcabcabcabc', 5],
        ];
    }

    public function testHtmlPurify()
    {
        $testString = <<<STR
<html dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="GENERATOR" content="MSHTML 10.00.9200.17228">
<style id="owaParaStyle">P {
	MARGIN-BOTTOM: 0px; MARGIN-TOP: 0px
}
</style>
</head>
<body fPStyle="1" ocsi="0">
<div style="direction: ltr;font-family: Tahoma;color: #000000;font-size: 10pt;">no subject</div>
<div style="direction: ltr;font-family: Tahoma;color: #000000;font-size: 10pt;">no subject2</div>
<span>same line</span><span>same line2</span>
<p>same line</p><p>same line2</p>
</body>
</html>
STR;

        $expected = <<<STR
no subject
no subject2
same linesame line2
same linesame line2
STR;

        $this->assertEquals($expected, $this->helper->purify($testString));
    }

    public function testEscape()
    {
        $testString = <<<HTML
<span>same line</span><span>same line2</span>
<p>same line</p><p>same line2</p>
<script type="text/javascript">alert("test");</script>
HTML;

        $expected = <<<HTML
<span>same line</span><span>same line2</span>
<p>same line</p><p>same line2</p>
&lt;script type="text/javascript"&gt;alert("test");&lt;/script&gt;
HTML;

        $this->assertEquals($expected, $this->helper->escape($testString));
    }

    /**
     * @dataProvider longStringProvider
     * @param $value
     * @param $expected
     */
    public function testStripLongWords($value, $expected)
    {
        $result = $this->helper->stripLongWords($value);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function longStringProvider()
    {
        return [
            [
                'value' =>
                    'HBxIZm21dqK1IQzZo7bs7UCg6vSCSJTJ1POZ2s3nCeuupRLg5cp27FiQIwayLSfi' .
                    '6xfvCJvUS7_L46g4wC33hlvYCPqFoxW58zQA6e3U_3Bqa6ny39ffUCd6ahQ8iQxm' .
                    'eICezJL_ncZuue_LRTaLnQSExbwHaBcDbqaouCijWSKLqSGFHc8fpamKdBaNSUx_' .
                    'wx91_DZ6t9Cj83M8KLY1ppSVFSHIOITvNTtwIgtbK2Ces2h4WZzBx_h1zZ94wmsy' .
                    'HN4lsszvt5duhAMd00nxv2SjXDPugNlmlGlMVBdEFJvOu_JEE1hFOPSvJnODDXsO' .
                    'MsAt82QUxFxMzeBiMNbCn7h2yuohuL47lNbWTuqab5i 8YNh8b0WutN011aG6A4s' .
                    'wHW56nsp4MwEbMvWJcNKQ8N75FE2JcO3Sf7LSDO5TvJfJUBfDh_LQQQdzwlP8ptC' .
                    'qvCgq6wtPdOTWkGp6iNIZuRJK_jflZBsaOg15bUxkP_3Gl2wyVIDbGeUI2qXz3n9' .
                    'iqPZt8SjM39j3tJyW_7B9uVxvxfh_3VQ0eLGgtq5kgceZlA6HhmCGet5OPTT4vX7' .
                    'PxrEDqrJQmxHO9REh7mmNAOqbmWJdulQENA21OG4fx33aoNRCKxH7vbyIAgR6p 2' .
                    'XBOwl1McEjepONSgmHK9hCOlz__WnfWQLR8faf7Mj5jnlLXUHiw7vYX5EzsfUzQ3' .
                    'drbMY9rl8uYPEvvtvrLNWf4C9nM51MnElP30uYat 8UxXljHvjVTuSMfC13DC35i' .
                    'l59UZ3AMPuZPS1npH6c4fXbNMlOu2LJkoqk GkIn3j6UiEc4w73Fqct7qQomCSB8' .
                    'WigHoZQIeydZauaoSvwPFoCoF3kQiPPVgtjMvKZUepRyIOjZbWysDHWNb_JvTPgS' .
                    'KgKpuIOsrCBdlzu9K6hbwKfPXlkoQJ6No8ZV_wZveFDH7Xe6ONrKKXrPVR1g71Do' .
                    'sFnRJQ6FSUhhq2JeCoDJimI7I71S9GwDHW25fpbbd8YAj2uzQ6ql5QY9MMAVVvTl' .
                    '7Ke_ga3wkjrzrF_p6FGUHxSDObknGJRsZUgECuIbC6vpO09_B_EANxxZ5K4lxC7w' .
                    '0OJhrWqg7DsSgfnFnZvtRoe4nvP8fiX_6TGgQP91hVZ__XgkelP_vwsGWoqGFMN4' .
                    'C6pvVZiRZr3hNmdEDh27VXBjPHGoKQLDigAZGmtMzVpS5_iJn2EIYKvO euNc0Mq' .
                    'MTtb08ZhDEFcTDKb6KaTw8CTHmj6AAUsC67vNx3rSy4L_BL5SZ255r1DLGild2Tb' .
                    '_DMSebLGkG6QJ6S61WSswucd6valoPpwc8XiVT0p6BRuPkFs_Kd9uLtjHBlV7dPc' .
                    'AGduruvunHoLNJVPlmARmPyaeEzk0_OPWO8y_2eMPIiiZYwkqOQoFpNFMg1E8LjR' .
                    '7V_4z9l9SnNw8Uw9p7pG1y9abGtKliMp44bdMMVzy2sGDzWpN3pKKa6PM483ed4T' .
                    'HtV49Vf9LRjSXgKPGH7DratRCaPgo24ER2yoffEvlf2SCSal5WF2XwNCVlrD1lAc' .
                    'hcvbZMC4AJfKHKT2ovIh6dbUhJmuFBzjkrqQkOxV4TIfc2bA00AQihqMVhHh1FJJ' .
                    '23KyzzP0ZnLNYv1oXc8Avr54mM9qKoC92hK4xnqTdVPh0gdzQPONqGquIcUQFRhF' .
                    'JfgsdjmNi7fZA2gDzxdmOI7znm1r HodtiRr8ELMSoC0koanGah7mxrQxXWcPnh4' .
                    'jTiuBangx2TO3SX DOUM_agf9SGm3a_UEG_Bg4Q7JV5rVVONVfG4jwomQ76JuG8M' .
                    'Js6 xtsWanFihntC5Ho2qAOVa83Tqgy1dxr96Wd9DjXC0NtTFuLZJUMK9k77rgjk' .
                    'F8ixejXncSn7Z6Ie02qynvx67QskbyWlFy2CNQGjBvLI7aQ480wIN0HDQJfPB2te' .
                    'h0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchYcLNAYQGu' .
                    '3h8UXNvNvyFp mHQinV26ubwDoPmrkZhQx5blQxAvBLnhvJFYVhJEdjjniuLsUoi' .
                    '7BNeF H33KtthuY9VxLgBzsgtAOzZh8vt3wXm0x3R4Lkak3PT8M60DOcerIv01UB' .
                    'AHHojZpCJMBmpMPEqcMsojrIjo0kGCeLm50t_SYI8F0AnbWb94MX29jNM7uiUa6H' .
                    'Lac6sJYlFiJ7J4K5QBuqHhyIuf1uvIE7OZv7vJVNiFQnKCJYbBITP_7hix5CoC6V' .
                    'WNeF6ojrYa_tdZQ__0t1liQWIXc98w1QQVx3qOCVHTUTQIYz2100wYPTfgIFXzru' .
                    '6uMJefyoh0HNzOsF995JzAIiBLB4POv4lJrxoQK3D2MalwPl2dgNxZSo7EzLxUTR' .
                    ' KCswJwpAZP8U_sGYbOzoZb2Dq6VZkveywaStDvcYipItqCz_yqmIgii149DdF3y' .
                    'l8lwHWh2XZ2vw8J7rokhNWLY7UIYD8kZ4Tdn5qWLCiWri1LghZEIop9GM2IScgSv' .
                    'NuhXZbfB0fEt5QpxkJPkgGoqOHvXoiDJcHW4u4Hhj9AaF_7w6dbuQZnUblc_bDsw' .
                    'LirXnbGVy2Ni9dIQ9cmQbNvjnkiDXxEeI020AxHbRYzKs0f7rwwWDg5ZAOkTOKWr' .
                    'JPnVrsH5BH_djZ9_OYdqzTngf9Kv5epWRJ4IdFGoRDaCIaC0FRZ8bzdqk8MmdBM7' .
                    'ZNAueKmZsXjgd3heeRUepYf1rvidFSifjPHqvZkHfDu8OOViWqVIicWLe9rNyvdG' .
                    'JcBdBPAw3hlvX0qkzBosekksXfuo2l8yRwGLzRXXL4Ax1gW7dHYtCNZMMqmSY54e' .
                    'M2PUw3AdZ7TjGQWtq6NzNYvXlPyzrgACpVijHz VC1xBzNE6I7PIQbNDYC2Dpn7O' .
                    'wLyf2n_XuI9EOiqKhGkHUpdOF_NPqbXajFq9WWcehwgVfG3sdeqexvkiF5T52wQM' .
                    'aOJhBcrlwYT4W9AxaJpClzFLL4tjLKDz7Duh4aC7sGOj29RA65IyUlZt87uN6KVa' .
                    '25g9upGT74FwhpWDFAql3wPkASNYf8Nz4qPPbiVXjSczTJS9CUdstn13dthfDSOZ' .
                    '_YNaWcamZJWTc68jzHJtU1NhblFaQXqQLBslE8eFNfGoyUuQERy_tnNIqa7UZmwf' .
                    'IZ_LfW3M 1VDJYRl04b037zmMVUIq_Z9z8i2vp9fac6DVBgEbgwj_1YsKUmxmO8B' .
                    'EZkLHl5RkwQLUbYIpC6eaJcddSegomQKBJ8Y8IfNCd9rcJ_Ht8120HlkZa0M7Gho' .
                    '8HnScfSQ2B1gfuIUQ9rDXb6vrh2BezrwcVXPQdQMCgQoB5cYm8YKx5He4HVgEXFa' .
                    '4viVaXVWGnsEniZicMuiEARBlDrbJV6d4XfIvUVN98jx8RqBDnLH RXHxwhz29aJ' .
                    'lqYUtH0jpfqPl7Zdo_8xtJexWilSuWOBja8foV8grHuVENuo9o9F_vteo1eFBdR6' .
                    'ZngxS6Uu0eLzV3z8l_iHl8CCFB21lqtgWTfQSI5gTfdDC4nSPINsbab8rVaqUm2x' .
                    'Ifd4Fjl4VRqsONXCmJD5kJK5yrXU54KohUnWK_6uCBo7UhygOARZJNkc79NnTD_D' .
                    'Uh_YDbPGFwc2ob5yow3ViybNaZqcf4hjS2OWGK2Q7lj9_A90oHlVCgOIDzczueID' .
                    'JJv2xuxyQmQP3piFNsuLGUYtDUhfVxAzBtf4AaiAgvo_LvcGCZBjJlzTCosznFe2' .
                    'M7aoeyjX3wq3NVBtegv5tDcSnPlRIqWqN0lynHAfgSyVl0ypBFMlX2BqH B2lq8s' .
                    'tnBZ8UgofSwwyELB9E7q5c1Oo91GWyNYBmAYCo0Q4lzL3ZtqBF5ciXleGPPJ2zRO' .
                    'RryyZqPxOBS5Q4EZSwbOgxB2',
                'expected' =>
                    '2XBOwl1McEjepONSgmHK9hCOlz__WnfWQLR8faf7Mj5jnlLXUHiw7vYX5EzsfUzQ' .
                    '3drbMY9rl8uYPEvvtvrLNWf4C9nM51MnElP30uYat 8UxXljHvjVTuSMfC13DC35' .
                    'il59UZ3AMPuZPS1npH6c4fXbNMlOu2LJkoqk HodtiRr8ELMSoC0koanGah7mxrQ' .
                    'xXWcPnh4jTiuBangx2TO3SX DOUM_agf9SGm3a_UEG_Bg4Q7JV5rVVONVfG4jwom' .
                    'Q76JuG8MJs6 xtsWanFihntC5Ho2qAOVa83Tqgy1dxr96Wd9DjXC0NtTFuLZJUMK' .
                    '9k77rgjkF8ixejXncSn7Z6Ie02qynvx67QskbyWlFy2CNQGjBvLI7aQ480wIN0HD' .
                    'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY' .
                    'cLNAYQGu3h8UXNvNvyFp mHQinV26ubwDoPmrkZhQx5blQxAvBLnhvJFYVhJEdjj' .
                    'niuLsUoi7BNeF 1VDJYRl04b037zmMVUIq_Z9z8i2vp9fac6DVBgEbgwj_1YsKUm' .
                    'xmO8BEZkLHl5RkwQLUbYIpC6eaJcddSegomQKBJ8Y8IfNCd9rcJ_Ht8120HlkZa0' .
                    'M7Gho8HnScfSQ2B1gfuIUQ9rDXb6vrh2BezrwcVXPQdQMCgQoB5cYm8YKx5He4HV' .
                    'gEXFa4viVaXVWGnsEniZicMuiEARBlDrbJV6d4XfIvUVN98jx8RqBDnLH B2lq8s' .
                    'tnBZ8UgofSwwyELB9E7q5c1Oo91GWyNYBmAYCo0Q4lzL3ZtqBF5ciXleGPPJ2zRO' .
                    'RryyZqPxOBS5Q4EZSwbOgxB2'
            ],
            [
                'value' => 'single',
                'expected' => 'single'
            ],
            [
                'value' => 'double   stripped',
                'expected' => 'double stripped'
            ]
        ];
    }
}
