<?php declare(strict_types=1);
require_once 'OneaccountEngineInterface.php';

class OneaccountTransientOneaccountEngine implements OneaccountEngineInterface
{
    private $expiration;


    /**
     * TransientEngine constructor.
     * @param $expiration
     */
    public function __construct($expiration)
    {
        $this->expiration = $expiration;
    }

    public function set(string $key, array $value)
    {
        return set_transient($key, $value, $this->expiration);
    }

    public function get(string $key)
    {
        $transient = get_transient($key);

        delete_transient($key);

        return $transient;
    }
}
