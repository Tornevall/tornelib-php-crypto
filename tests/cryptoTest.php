<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\MODULE_CRYPTO;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once(__DIR__ . '/../vendor/autoload.php');
}

class cryptoTest extends TestCase
{
    /** @var $Crypto MODULE_CRYPTO */
    private $Crypto;

    // Compressed strings setup over base64
    private $testCompressString = "Testing my string";

    private $testStatic16k = "dbe98c0a8d49a9fa";
    private $testStatic16i = "a16420a8f2bad32e";

    private $encoded = [
        'aes' => 'Z_UWXTvTYTnFS4J0jS7g23oMv07uEdJuu0alwNsXqqU',
        'aesopenssl' => '7A1OoExi-lj_O3oyIky3o3NUbJxyoNtSSaoBsNZJI1A',
    ];

    private $GZ_COMPRESSION_BASE = "H4sIAAAAAAAEAwERAO7_VGVzdGluZyBteSBzdHJpbmf030_XEQAAAA";
    private $GZ_COMPRESSION_9 = "H4sIAAAAAAACAwtJLS7JzEtXyK1UKC4pArIA9N9P1xEAAAA";
    private $BZ2_COMPRESSION = "QlpoNDFBWSZTWajSZJAAAAETgEAABAACoxwgIAAhoaA0IBppoKc4F16DpQXi7kinChIVGkySAA";

    private $testLongCompressString = "The following string contains data: This is a longer string to test the best compression on something that is worth compression.";
    private $testLongCompressedString = "H4sIAAAAAAACA02MQQrDMAwEv6IX5N68Ix9QU9UyONpgLQTy-tqFQmFg9zBMuR_r5iZvtIarRpFkn7MjqDVSXkpdZfOaMlBpiGL9pxFCSwpH4znPjuPsllkRMkgcRv-arpyFC53-ry0flwqd0IQAAAA";


    public function setUp()
    {
        $this->Crypto = new MODULE_CRYPTO();
    }

    public function setSimpleKeys()
    {
        $this->Crypto->setAesKey($this->testStatic16k, false);
        $this->Crypto->setAesIv($this->testStatic16i, false);
    }

    /**
     * @test
     * @throws Exception
     */
    public function base64GzEncodeLevel0()
    {
        $gzString = $this->Crypto->base64_gzencode($this->testCompressString, 0);
        static::assertTrue($gzString == $this->GZ_COMPRESSION_BASE);
    }

    /**
     * @test
     * @throws Exception
     */
    public function aesEncryptDeprecated()
    {
        $this->setSimpleKeys();
        $enc = $this->Crypto->aesEncrypt($this->testCompressString);
        static::assertTrue($enc == $this->encoded['aes'] || $enc == $this->encoded['aesopenssl']);
    }

    /**
     * @test
     * @throws Exception
     */
    public function aesDecryptDeprecated()
    {
        $this->setSimpleKeys();
        if (version_compare(PHP_VERSION, '7.3', '>=')) {
            static::markTestSkipped('mcrypt is active but not supported by PHP 7.3');
        }
        $this->Crypto->setMcrypt(true);
        static::assertTrue($this->Crypto->aesDecrypt($this->encoded['aes']) == $this->testCompressString);
    }

    /**
     * @test
     * @throws Exception
     */
    public function aesDecryptDeprecatedPassOpenSsl()
    {
        $this->setSimpleKeys();
        static::assertTrue($this->Crypto->aesDecrypt($this->encoded['aesopenssl']) == $this->testCompressString);
    }

    /**
     * @test
     * @throws Exception
     */
    public function aesOpenSslDefault()
    {
        $this->setSimpleKeys();
        $enc = $this->Crypto->getEncryptSsl($this->testCompressString);
        static::assertTrue($enc == $this->encoded['aesopenssl']);
    }

    /**
     * @test
     * @throws Exception
     */
    public function aesOpenSslOtherAes()
    {
        $this->setSimpleKeys();
        static::assertTrue(
            strtolower(
                $this->Crypto->getCipherTypeByString(
                    $this->encoded['aesopenssl'],
                    $this->testCompressString
                )
            ) == "aes-256-cbc"
        );
    }

    /**
     * @test
     */
    function aesOpenSslDecrypt()
    {
        $this->setSimpleKeys();
        static::assertTrue($this->Crypto->getDecryptSsl($this->encoded['aesopenssl']) == $this->testCompressString);
    }

    /**
     * @test
     * @throws Exception
     */
    public function base64GzEncodeLevel9()
    {
        $myString = "Testing my string";
        $gzString = $this->Crypto->base64_gzencode($myString, 9);
        static::assertTrue($gzString == $this->GZ_COMPRESSION_9);
    }

    /**
     * @test
     * @throws Exception
     */
    public function base64GzDecodeLevel0()
    {
        $gzString = $this->Crypto->base64_gzdecode($this->GZ_COMPRESSION_BASE);
        static::assertTrue($gzString == $this->testCompressString);
    }

    /**
     * @test
     * @throws Exception
     */
    public function base64GzDecodeLevel9()
    {
        $gzString = $this->Crypto->base64_gzdecode($this->GZ_COMPRESSION_9);
        static::assertTrue($gzString == $this->testCompressString);
    }

    /**
     * @test
     * @throws Exception
     */
    public function base64BzEncode()
    {
        if (function_exists('bzcompress')) {
            $bzString = $this->Crypto->base64_bzencode($this->testCompressString);
            static::assertTrue($bzString == $this->BZ2_COMPRESSION);
        } else {
            static::markTestSkipped('bzcompress is missing on this server, could not complete test');
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function base64BzDecode()
    {
        if (function_exists('bzcompress')) {
            $bzString = $this->Crypto->base64_bzdecode($this->BZ2_COMPRESSION);
            static::assertTrue($bzString == $this->testCompressString);
        } else {
            $this->markTestSkipped('bzcompress is missing on this server, could not complete test');
        }
    }

    /**
     * @test
     * @throws Exception
     * @throws Exception
     */
    public function bestCompression()
    {
        $compressedString = $this->Crypto->base64_compress($this->testLongCompressString);
        $uncompressedString = $this->Crypto->base64_decompress($compressedString);
        $uncompressedStringCompressionType = $this->Crypto->base64_decompress($compressedString, true);
        // In this case the compression type has really nothing to do with the test. We just know that gz9 is the best type for our chosen data string.
        static::assertTrue($uncompressedString == $this->testLongCompressString && $uncompressedStringCompressionType == "gz9");
    }

    /**
     * @test
     */
    public function mkPass()
    {
        static::assertTrue(strlen($this->Crypto->mkpass(1, 16)) == 16);
    }

    /**
     * @test
     */
    public function randomSaltStaticMkPass()
    {
        static::assertTrue(strlen(MODULE_CRYPTO::getRandomSalt(1, 16)) == 16);
    }

    /**
     * @test
     */
    public function randomSaltStaticMkPassBackwardCompat()
    {
        static::assertTrue(strlen(\TorneLIB\TorneLIB_Crypto::getRandomSalt(1, 16)) == 16);
    }
}
