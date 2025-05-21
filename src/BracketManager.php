<?php

namespace MrNamra\BracketManager;

use MrNamra\BracketManager\Repository\BracketManagerRepository;
use MrNamra\BracketManager\Repository\SeedingManager;

class BracketManager
{
    public static function boot()
    {
        return new BracketManagerRepository(new SeedingManager());
    }
}
