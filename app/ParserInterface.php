<?php

namespace App;

interface  ParserInterface
{
    public function run(string $url);

    public function fetch(string $url): string;

    public function getDetailsInfo(string $url);

    public function getCurrentPage(string $url): int;
}