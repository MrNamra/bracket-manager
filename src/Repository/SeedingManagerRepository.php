<?php

namespace MrNamra\BracketManager\Repository;

use MrNamra\BracketManager\Interfaces\SeedingManagerInterface;

class SeedingManagerRepository implements SeedingManagerInterface
{
    public function getNeturalSeeding(array $participants): array
    {
        $n = count($participants);
        $power = pow(2, ceil(log($n, 2)));
        $participants = array_pad($participants, $power, null);

        return [
            'paticipents' => $participants,
            'size' => $n
        ];
    }
    public function getReverseSeeding(array $participants): array
    {
        $participants = $this->getNeturalSeeding($participants);

        return [
            'paticipents' => array_reverse($participants['paticipents']),
            'size' => $participants['size']
        ];
    }
    public function getHalfShiftSeeding(array $participants, bool $return = false): array
    {
        $participants = $this->getNeturalSeeding($participants);
        $half = count($participants['paticipents']) / 2;

        $firstHalf = array_slice($participants['paticipents'], 0, $half);
        $secondHalf = array_slice($participants['paticipents'], $half);

        if ($return) {
            return [$firstHalf, $secondHalf];
        }

        return [
            'paticipents' => array_merge($secondHalf, $firstHalf),
            'size' => $participants['size']
        ];
    }
    public function getReverseHalfShiftSeeding(array $participants): array
    {
        $p = $this->getHalfShiftSeeding($participants, true);
        $firstHalf = array_reverse($p[0]);
        $secondHalf = array_reverse($p[1]);
        $p = array_merge($firstHalf, $secondHalf);
        return ['paticipents' => $p, 'size' => count($p)];
    }
    public function getPairFlipSeeding(array $participants): array
    {
        $p = $this->getNeturalSeeding($participants)['paticipents'];

        for ($i = 0; $i < count($p); $i += 2) {
            if (array_key_exists($i + 1, $p)) {
                [$p[$i], $p[$i + 1]] = [$p[$i + 1], $p[$i]];
            }
        }
        return ['paticipents' => $p, 'size' => count($p)];
    }
    public function getInnerOuterSeeding(array $participants): array
    {
        $p = $this->getNeturalSeeding($participants)['paticipents'];
        $pCount = count($p);
        $rounds = ceil(log($pCount, 2));

        if ($pCount < 2) {
            return [];
        }

        $matches = [[1, 2]];

        for ($round = 1; $round < $rounds; $round++) {
            $matchesRound = [];
            $sum = pow(2, $round + 1) + 1;

            foreach ($matches as $match) {
                $home = changeIntoBye($match[0], $pCount);
                $away = changeIntoBye($sum - $match[0], $pCount);
                $matchesRound[] = [$home, $away];

                $home = changeIntoBye($sum - $match[1], $pCount);
                $away = changeIntoBye($match[1], $pCount);
                $matchesRound[] = [$home, $away];
            }

            $matches = $matchesRound;
        }

        $seeding = [];
        foreach ($matches as $match) {
            foreach ($match as $seed) {
                $seeding[] = $seed;
            }
        }

        $paticipents = array_map(function ($index) use ($p) {
            return $index !== null ? $p[$index - 1] : null;
        }, $seeding);
        return [
            'paticipents' => $paticipents,
            'size' => $pCount
        ];
    }
    public function getHalfShiftInnerOuterSeeding(array $participants): array
    {
        $participants = $this->getNeturalSeeding($participants)['paticipents'];
        $n = count($participants);

        $posotion = $this->getSeedingShift($n);
        $seedings = [];
        foreach ($posotion as $i) {
            $seedings[] = $participants[$i - 1];
        }
        return [
            'paticipents' => $seedings,
            'size' => $n
        ];
    }
    private function getSeedingShift(int $n): array
    {
        if ($n == 1) {
            return [1];
        }
        $prev = generateBracket($n / 2);
        $bracket = [];
        foreach ($prev as $seed) {
            $bracket[] = $seed;
            $bracket[] = $n + 1 - $seed;
        }
        return $bracket;
    }
}
