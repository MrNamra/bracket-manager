<?php

namespace MrNamra\BracketManager\Interfaces;

interface ObjectCreatorInterface
{
    public function getBracketObject(array $stage): array;
}
