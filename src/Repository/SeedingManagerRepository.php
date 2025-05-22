<?php

namespace MrNamra\BracketManager\Repository;

use MrNamra\BracketManager\Interfaces\SeedingManagerInterface;

class SeedingManagerRepository implements SeedingManagerInterface
{
    public function getNeturalSeeding(array $paticipents): array
    {
        $numberOfParicipents = count($paticipents);
        if (isPowerOfTwo($numberOfParicipents)) {
            return [];
        } else {
            $bracketSize = nextPowerOfTwo($numberOfParicipents);
            for ($i = $numberOfParicipents; $i < $bracketSize; $i++) {
                $paticipents[] = null;
            }

            return [
                'paticipents' => $paticipents,
                'size' => nextPowerOfTwo($numberOfParicipents)
            ];
        }
    }
}
