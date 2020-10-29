<?php

namespace TorneLIB;

use Exception;
use PHPUnit\Framework\TestCase;
use TorneLIB\Data\Compress;

require_once(__DIR__ . '/../vendor/autoload.php');

class compressTest extends TestCase
{
    /**
     * @test
     */
    public function getGzEncode()
    {
        static::assertNotEmpty(
            (new Compress())->getGzEncode('Hello World')
        );
    }

    /**
     * @test
     * @throws Exception
     */
    public function getGzDecode()
    {
        static::assertSame(
            (new Compress())->getGzDecode((new Compress())->getGzEncode('Hello World')), 'Hello World'
        );
    }

    /**
     * @test
     * @throws Exception
     */
    public function getBzDecode()
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
     * @test
     * @throws Exception
     */
    public function getBzEncode()
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

    /**
     * @test
     */
    public function getGzEncodeOld()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        static::assertNotEmpty(
            (new Compress())->base64_gzencode('Hello World')
        );
    }
}
