<?php

namespace MrNamra\BracketManager\Repository;

use MrNamra\BracketManager\Interfaces\SeedingManagerInterface;

class SeedingManager implements SeedingManagerInterface
{
    public function getNeturalSeeding(array $paticipents): array
    {
        if (isPowerOfTwo(count($paticipents))) {
        } else {
            $bracketSize = nextPowerOfTwo(count($paticipents));
            for ($i = count($paticipents); $i < $bracketSize; $i++) {
                $paticipents[] = null;
            }
            return $paticipents;
        }
        return [];
    }
}
