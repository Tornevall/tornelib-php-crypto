<?php

namespace TorneLIB\Data;

use Exception;
use TorneLIB\Config\Flag;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\IO\Data\Strings;

/**
 * Class Crypto
 * @package TorneLIB\Data
 * @version 6.1.0
 */
class Crypto
{
    const COMPLEX_UPPER = 1;
    const COMPLEX_LOWER = 2;
    const COMPLEX_NUMERICS = 4;
    const COMPLEX_SPECIAL = 8;
    const COMPLEX_BINARY = 16;

    const CRYPTO_UNAVAILABLE = 0;
    const CRYPTO_SSL = 1;
    const CRYPTO_MCRYPT = 2;

    /**
     * @var Password
     */
    private $password;

    /**
     * @var string
     * @since 6.1.0
     */
    private $aesKey;

    /**
     * @var string
     * @since 6.1.0
     */
    private $aesIv;

    /**
     * @var int
     * @since 6.1.0
     */
    private $aesIvLength;

    /**
     * @var int
     * @since 6.1.0
     */
    private $compressionLevel = 5;

    /**
     * @var
     */
    private $cryptoLib;

    /**
     * @var bool $canMcrypt
     */
    private $canMcrypt = false;

    /**
     * @var
     */
    private $sslCipherType;

    /**
     * Crypto constructor.
     * @since 6.0
     */
    public function __construct()
    {
        $this->password = new Password();
        $this->setCryptoLib();

        return $this;
    }

    /**
     * openssl has higher priority.
     *
     * @return $this
     * @throws Exception
     */
    private function setCryptoLib()
    {
        if (function_exists('mcrypt_encrypt')) {
            $this->canMcrypt = true;
        }

        if (function_exists('openssl_encrypt')) {
            $this->cryptoLib = Crypto::CRYPTO_SSL;
            $this->setCipher();
        } elseif (function_exists('mcrypt_encrypt')) {
            $this->cryptoLib = Crypto::CRYPTO_MCRYPT;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getCryptoLib()
    {
        return $this->cryptoLib;
    }

    /**
     * @param $key
     * @param $iv
     * @param string $method
     * @return Crypto
     * @since 6.1.0
     */
    public function setAesKeys($key, $iv, $method = 'sha1')
    {
        if (Flag::getFlag('mcrypt') && $method === 'sha1') {
            $method = 'md5';
        }

        if ($method === 'sha1') {
            $this->aesKey = sha1($key);
            $this->aesIv = sha1($iv);
        } elseif ($method === 'md5') {
            $this->aesKey = md5($key);
            $this->aesIv = md5($iv);
        } else {
            $this->aesKey = $key;
            $this->aesIv = $iv;
        }

        return $this;
    }

    /**
     * @param bool $adjustLength
     * @return mixed
     * @since 6.0.15
     */
    public function getAesIv($adjustLength = true)
    {
        if (function_exists('openssl_cipher_iv_length')) {
            if ($adjustLength) {
                $this->aesIvLength = openssl_cipher_iv_length($this->getSslCipherType());
                if ((int)$this->aesIvLength >= 0) {
                    if (strlen($this->aesIv) > $this->aesIvLength) {
                        $this->aesIv = substr($this->aesIv, 0, $this->aesIvLength);
                    }
                }
            }
        }
        return $this->aesIv;
    }

    /**
     * @return mixed
     * @since 6.0.15
     */
    public function getAesKey()
    {
        return $this->aesKey;
    }

    /**
     * @param int $compressionLevel
     * @since 6.1.0
     */
    public function setCompressionLevel($compressionLevel = 9)
    {
        $this->compressionLevel = $compressionLevel;
    }

    /**
     * @return int
     * @since 6.1.0
     */
    public function getCompressionLevel()
    {
        return $this->compressionLevel;
    }

    /**
     * @return int
     */
    public function getAesIvLength()
    {
        return $this->aesIvLength;
    }

    public function mkpass(
        $complexity = self::COMPLEX_UPPER + self::COMPLEX_LOWER + self::COMPLEX_NUMERICS,
        $totalLength = 16,
        $ambigous = true,
        $antiDouble = true
    ) {
        return $this->password->mkpass(
            $complexity,
            $totalLength,
            $ambigous,
            $antiDouble
        );
    }

    /**
     * @param string $cipherConstant
     * @return Crypto
     * @throws Exception
     */
    public function setCipher($cipherConstant = 'AES-256-CBC')
    {
        $cipherMethods = openssl_get_cipher_methods();

        if (
            is_array($cipherMethods) &&
            in_array(
                strtolower($cipherConstant),
                array_map('strtolower', $cipherMethods)
            )
        ) {
            $this->sslCipherType = $cipherConstant;

            return $this;
        }

        throw new Exception(
            'Cipher does not exists in this openssl module',
            Constants::LIB_SSL_CIPHER_UNAVAILABLE
        );
    }

    public function aesEncrypt($dataToEncrypt = '', $asBase64 = true, $forceUtf8 = true)
    {
        if (Flag::getFlag('mcrypt') && $this->canMcrypt) {
            $return = $this->getEncryptedMcrypt(
                $dataToEncrypt,
                $asBase64,
                $forceUtf8
            );
        } else {
            $return = $this->getEncryptedSsl(
                $dataToEncrypt,
                $asBase64,
                $forceUtf8
            );
        }

        return $return;
    }

    /**
     * @param string $dataToEncrypt
     * @param bool $asBase64
     * @param bool $forceUtf8
     * @return false|string
     * @throws ExceptionHandler
     * since 6.1.0
     */
    private function getEncryptedSsl($dataToEncrypt = '', $asBase64 = true, $forceUtf8 = true)
    {
        if (empty($this->aesKey) || empty($this->aesIv)) {
            throw new ExceptionHandler(
                'You need to set KEY and IV to encrypt content.',
                Constants::LIB_SSL_CIPHER_NO_KEYS
            );
        }

        $return = openssl_encrypt(
            $forceUtf8 ? utf8_encode($dataToEncrypt) : $dataToEncrypt,
            $this->getSslCipherType(),
            $this->getAesKey(),
            OPENSSL_RAW_DATA,
            $this->getAesIv(true)
        );

        if ($asBase64) {
            $return = (new Strings())->base64urlEncode($return);
        }

        return $return;
    }

    /**
     * Statically encrypting with RIJNDAEL_256.
     *
     * @param string $dataToEncrypt
     * @param bool $asBase64
     * @param bool $forceUtf8
     * @return string
     */
    private function getEncryptedMcrypt(
        $dataToEncrypt = '',
        $asBase64 = true,
        $forceUtf8 = true
    ) {
        $return = mcrypt_encrypt(
            MCRYPT_RIJNDAEL_256,
            $this->getAesKey(),
            $forceUtf8 ? utf8_encode($dataToEncrypt) : $dataToEncrypt,
            MCRYPT_MODE_CBC,
            $this->getAesIv(false)
        );

        if ($asBase64) {
            $return = (new Strings())->base64urlEncode($return);
        }

        return $return;
    }

    /**
     * @param $dataToDecrypt
     * @param bool $asBase64
     * @return false|string
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function aesDecrypt($dataToDecrypt, $asBase64 = true)
    {
        if (empty($this->aesKey) || empty($this->aesIv)) {
            throw new ExceptionHandler(
                'You need to set KEY and IV to encrypt content.',
                Constants::LIB_SSL_CIPHER_NO_KEYS
            );
        }

        return $this->getDecryptedSsl($dataToDecrypt, $asBase64);
    }

    /**
     * @param $dataToDecrypt
     * @param $asBase64
     * @return false|string
     * @since 6.1.0
     */
    public function getDecryptedSsl($dataToDecrypt, $asBase64)
    {
        if ($asBase64) {
            $dataToDecrypt = (new Strings())->base64urlDecode($dataToDecrypt);
        }

        $return = openssl_decrypt(
            $dataToDecrypt,
            $this->getSslCipherType(),
            $this->getAesKey(),
            OPENSSL_RAW_DATA,
            $this->getAesIv(true)
        );

        return $return;
    }

    /**
     * @return mixed
     */
    public function getSslCipherType()
    {
        return $this->sslCipherType;
    }

    /**
     * @param $encrypted
     * @param $decrypted
     * @return string
     */
    public function getCipherTypeByString($encrypted, $decrypted)
    {
        $return = '';

        $originalKey = $this->getAesKey();
        $originalIv = $this->getAesIv(false);
        if ($this->getCryptoLib() === self::CRYPTO_SSL) {
            $cipherTypes = openssl_get_cipher_methods();
            foreach ($cipherTypes as $type) {
                try {
                    $this->setCipher($type);
                    $this->setAesKeys($originalKey, $originalIv, 'plain');
                    $result = $this->getEncryptedSsl($decrypted);
                    if (!empty($result) && $result === $encrypted) {
                        $return = $type;
                        break;
                    }
                } catch (Exception $e) {
                }
            }
        }

        return (string)$return;
    }
}
