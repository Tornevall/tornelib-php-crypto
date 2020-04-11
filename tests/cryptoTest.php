<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Config\Flag;
use TorneLIB\Data\Crypto;
use TorneLIB\Data\Password;
use TorneLIB\Exception\ExceptionHandler;

require_once(__DIR__ . '/../vendor/autoload.php');

class cryptoTest extends TestCase
{
    /**
     * @test
     */
    public function getMkPassUpper()
    {
        $cryptoClass = new Crypto();
        $genUpper = $cryptoClass->mkpass(
            Crypto::COMPLEX_UPPER,
            null,
            null,
            true
        );

        static::assertTrue(
            $genUpper === strtoupper($genUpper) &&
            strlen(16)
        );
    }

    /**
     * @test
     */
    public function getMkPassLower()
    {
        $cryptoClass = new Crypto();
        $genLower = $cryptoClass->mkpass(
            Crypto::COMPLEX_LOWER,
            null,
            null,
            true
        );

        static::assertTrue(
            $genLower === strtolower($genLower) &&
            strlen(16)
        );
    }

    /**
     * @test
     */
    public function getMkPassUpperLower()
    {
        $cryptoClass = new Crypto();
        $genUpperAndLower = $cryptoClass->mkpass(
            Crypto::COMPLEX_UPPER + Crypto::COMPLEX_LOWER,
            null,
            null,
            true
        );

        static::assertTrue(
            $genUpperAndLower !== strtoupper($genUpperAndLower) &&
            $genUpperAndLower !== strtolower($genUpperAndLower) &&
            strlen(16)
        );
    }

    /**
     * @test
     */
    public function getMkPassWithoutParams()
    {
        $cryptoClass = new Crypto();
        $genUpperAndLower = $cryptoClass->mkpass(null, 20);
        static::assertTrue(
            $genUpperAndLower !== strtoupper($genUpperAndLower) &&
            $genUpperAndLower !== strtolower($genUpperAndLower) &&
            strlen(20)
        );
    }

    /**
     * @test
     */
    public function getMiniPass()
    {
        $passwordClass = new Password();

        static::assertTrue(!empty($passwordClass->mkpass(
            Password::COMPLEX_UPPER
        )));
    }

    /**
     * @test
     */
    public function getCryptoLib()
    {
        static::assertTrue(
            (new Crypto())->getCryptoLib() === Crypto::CRYPTO_SSL
        );
    }

    /**
     * @test
     */
    public function getEncryptedString()
    {
        $encData = (new Crypto())
            ->setAesKeys('MyKey', 'MyIV')
            ->aesEncrypt('EncryptME');

        static::assertTrue(
            $encData === 'U5Te2R-G-sxgBIC-FXkdXA'
        );
    }

    /**
     * @test
     */
    public function getEncryptedStringMcrypt()
    {
        Flag::setFlag('mcrypt', true);

        $encData = (new Crypto())
            ->setAesKeys('MyKey', 'MyIV')
            ->aesEncrypt('EncryptME');

        static::assertTrue(
            $encData === '2qKNH_JrlZHyq-nJFaFR5gC2J5iFD7rFFts6Ikr7IMY'
        );

        Flag::deleteFlag('mcrypt');
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getDecryptedString()
    {
        $encData = (new Crypto())
            ->setAesKeys('MyKey', 'MyIV')
            ->aesEncrypt('EncryptME');

        $decData = (new Crypto())
            ->setAesKeys('MyKey', 'MyIV')
            ->aesDecrypt($encData);

        static::assertTrue(
            $decData === 'EncryptME'
        );
    }

    /**
     * @test
     */
    public function discoverCipher()
    {
        $crypt = (new Crypto())->setAesKeys('one_key', 'one_aes');
        $encrypted = $crypt->aesEncrypt(
            'encryptThis'
        );

        static::assertTrue(
            $crypt->getCipherTypeByString(
                $encrypted,
                'encryptThis'
            ) === 'aes-256-cbc'
        );
    }
}
