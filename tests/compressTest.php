<?php

namespace TorneLIB;

use Exception;
use PHPUnit\Framework\TestCase;
use TorneLIB\Data\Compress;

require_once(__DIR__ . '/../vendor/autoload.php');

class compressTest extends TestCase
{
    public function testGetGzEncode()
    {
        static::assertNotEmpty(
            (new Compress())->getGzEncode('Hello World')
        );
    }

    /**
     * @throws Exception
     */
    public function testGetGzDecode()
    {
        static::assertSame(
            (new Compress())->getGzDecode((new Compress())->getGzEncode('Hello World')), 'Hello World'
        );
    }

    /**
     * @throws Exception
     */
    public function testGetBzDecode()
    {
        if ((new Utils\Security())->getFunctionState('bzcompress', false)) {
            static::assertSame(
                (new Compress())->getBzDecode(
                    (new Compress())->getBzEncode('Hello world')
                ), 'Hello world'
            );
            return;
        }
        static::markTestSkipped(
            sprintf('%s: bzcompress is missing in this platform.', __FUNCTION__)
        );
    }

    /**
     * @throws Exception
     */
    public function testGetBzEncode()
    {
        if ((new Utils\Security())->getFunctionState('bzcompress', false)) {
            static::assertNotEmpty(
                (new Compress())->getBzEncode('Hello World')
            );
            return;
        }
        static::markTestSkipped(
            sprintf('%s: bzcompress is missing in this platform.', __FUNCTION__)
        );
    }

    public function testGetGzEncodeOld()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        static::assertNotEmpty(
            (new Compress())->base64_gzencode('Hello World')
        );
    }
}
