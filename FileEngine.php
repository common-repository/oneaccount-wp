<?php
require_once "EngineInterface.php";

final class FileEngine implements EngineInterface
{

    public function set(string $key, array $value)
    {
        return false !== file_put_contents($key . '.txt', json_encode($value));
    }

    public function get(string $key)
    {
        $data = file_get_contents($key . '.txt');
        unlink($key . '.txt');

        return json_decode($data, true);
    }
}