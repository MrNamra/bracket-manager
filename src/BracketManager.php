<?php

namespace MrNamra\BracketManager;

use MrNamra\BracketManager\Repository\BracketManagerRepository;
use MrNamra\BracketManager\Repository\ObjectCreatorRepository;
use MrNamra\BracketManager\Repository\SeedingManagerRepository;

class BracketManager
{
    public static function boot(): object
    {
        return new BracketManagerRepository(new SeedingManagerRepository(), new ObjectCreatorRepository());
    }
}
