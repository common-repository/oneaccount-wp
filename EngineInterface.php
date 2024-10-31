<?php


interface EngineInterface
{
    public function set(string $key, array $value);

    public function get(string $key);
}