<?php

namespace TorneLIB\Data;

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
     * @var Aes
     */
    private $Aes;

    /**
     * @var int
     * @since 6.1.0
     */
    private $compressionLevel = 5;

    /**
     * Crypto constructor.
     * @since 6.0
     */
    public function __construct()
    {
        $this->password = new Password();
        $this->Aes = new Aes();

        return $this;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->Aes, $name)) {
            return call_user_func_array(
                [
                    $this->Aes,
                    $name,
                ],
                $arguments
            );
        } elseif (method_exists($this->password, $name)) {
            return call_user_func_array(
                [
                    $this->password,
                    $name,
                ],
                $arguments
            );
        }
    }
}
