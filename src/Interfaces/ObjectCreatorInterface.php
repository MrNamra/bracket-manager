<?php

namespace MrNamra\BracketManager\Interfaces;

interface ObjectCreatorInterface
{
    public function getBracketObject(array $stage): array;
    public function addScore(array $brackectObj, array $score): array;
}
