<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Config\Flag;
use TorneLIB\Data\Aes;
use TorneLIB\Data\Crypto;
use TorneLIB\Data\Password;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\MODULE_CRYPTO;

require_once(__DIR__ . '/../vendor/autoload.php');

class cryptoTest extends TestCase
{
    /**
     * @test
     * Get uppercase only "keycode".
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
    public function getMkPassLong()
    {
        $getLength = 255;
        $pwClass = new Password();
        $pwClassPassword = $pwClass->mkpass(Password::COMPLEX_UPPER + Password::COMPLEX_LOWER, $getLength);

        $modCryptoClass = new MODULE_CRYPTO();
        $cryptoClassPw = $modCryptoClass->mkpass(Password::COMPLEX_UPPER + Password::COMPLEX_LOWER, $getLength);

        static::assertTrue(
            strlen($pwClassPassword) === $getLength &&
            strlen($cryptoClassPw) === $getLength
        );
    }

    /**
     * @test
     * Get lowercase only "keycode".
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
     * Get mixed keycode (upper+lowercase).
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
     * Get "default" keycode string.
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
     * Get uppercase keycode directly from password class.
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
     * Test if openssl is present as most of the crypto class is depending on it.
     */
    public function getCryptoLib()
    {
        static::assertSame(
            (new Aes())->getCryptoLib(), Crypto::CRYPTO_SSL
        );
    }

    /**
     * @test
     * Test basic encryption. This encryption was in 6.0 similar in both mcrypt and openssl.
     */
    public function getEncryptedString()
    {
        /** @var Aes $crypto */
        $crypto = (new Crypto())
            ->setAesKeys('MyKey', 'MyIV');
        $encData = $crypto
            ->aesEncrypt('EncryptME');

        // First string is openssl encrypted.
        // Second string is mcrypt encrypted.
        static::assertTrue(
            $encData === 'U5Te2R-G-sxgBIC-FXkdXA' ||
            $encData === '2qKNH_JrlZHyq-nJFaFR5gC2J5iFD7rFFts6Ikr7IMY'
        );
    }

    /**
     * @test
     * Test basic encryption when openssl isn't there.
     */
    public function getEncryptedStringMcrypt()
    {
        Flag::setFlag('mcrypt', true);

        $encData = (new Crypto())
            ->setAesKeys('MyKey', 'MyIV')
            ->aesEncrypt('EncryptME');

        static::assertTrue(
            $encData === '2qKNH_JrlZHyq-nJFaFR5gC2J5iFD7rFFts6Ikr7IMY' ||
            $encData === 'x8NUlCoEg_OAdijHvPBj_g'
        );

        Flag::deleteFlag('mcrypt');
    }

    /**
     * @test
     */
    public function getDecryptedString()
    {
        $encData = (new Crypto())
            ->setAesKeys('MyKey', 'MyIV')
            ->aesEncrypt('EncryptME');

        $decData = (new Crypto())
            ->setAesKeys('MyKey', 'MyIV')
            ->aesDecrypt($encData);

        static::assertSame(
            $decData, 'EncryptME'
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

        $typeByString = $crypt->getCipherTypeByString(
            $encrypted,
            'encryptThis'
        );

        static::assertTrue(
            ($typeByString === 'aes-256-cbc' ? true : false)
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function findingMcrypt()
    {
        /** @var Aes $crypto */
        $crypto = new Crypto();
        $crypto->setAesKeys('MyKey', 'MyIV');
        try {
            $crypto->setMcryptOverSsl(true);
        } catch (Exception $e) {
            if ($e->getCode() === Constants::LIB_METHOD_OR_LIBRARY_UNAVAILABLE) {
                static::markTestSkipped(
                    sprintf('mcrypt is not available in PHP %s', PHP_VERSION)
                );
                return;
            }
        }

        $encData = $crypto->aesEncrypt('EncryptME');
        $findData = $crypto->getCipherTypeByString($encData, 'EncryptME');

        // Note to self: For current version of crypto, openssl and mcrypt defaults to different
        // encryption algorithms and types. By forcing mcrypt at this moment may break something.
        // Also be aware of that if you run something older, this might also break communication.
        static::assertTrue(empty($findData));
    }

    /**
     * @test
     */
    public function canCrypto()
    {
        // For the moment both Aes and Crypto has the Crypto-available constants, but
        // all CRYPTO_UNAVAILABLE should be returned from Crypto::class.
        $aes = new Aes();
        $crypto = new Crypto();

        static::assertTrue(
            $aes->canCrypto() !== Crypto::CRYPTO_UNAVAILABLE &&
            $crypto->canCrypto() !== Crypto::CRYPTO_UNAVAILABLE
        );
    }
}
