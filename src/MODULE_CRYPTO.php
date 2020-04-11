<?php

namespace TorneLIB;

use TorneLIB\Data\Crypto;

/**
 * Class MODULE_CRYPTO
 * @package TorneLIB
 * @version 6.1.0
 * @since 6.0
 * @deprecated Use v6.1 classes instead!!
 */
class MODULE_CRYPTO
{
    private $realCrypto;

    public function __construct()
    {
        $this->realCrypto = new Crypto();

        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @since 6.1.0
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->realCrypto, $name], $arguments);
    }
}