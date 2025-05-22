<?php

namespace MrNamra\BracketManager\Repository;

use MrNamra\BracketManager\Interfaces\ObjectCreatorInterface;

class ObjectCreatorRepository implements ObjectCreatorInterface
{
    private $object;
    public function getBracketObject(array $stage): array
    {
        $this->object = [
            'id' => $stage['id'],
            'tournament_id' => $stage['tournament_id'],
            'number' => $stage['number'],
            'name' => $stage['name'],
            'type' => $stage['type'],
            'settings' => $stage['settings'],
        ];

        $this->object['participant'] = $this->getParticipantObject($stage['seeding'], $stage['tournament_id']);

        $this->object['group'] = $this->getGroupObject($stage);
        $this->object['round'] = $this->getRoundObject($this->object);
        // $this->object['match'] = $this->getMatchObject($this->object);
        $this->object['match'] = $this->getMatchObject($stage);

        return $this->object;
    }
    private function getParticipantObject(array $seeding, int $tournament_id): array
    {
        $participant = [];
        foreach ($seeding as $key => $player) {
            if ($player === null) {
                continue;
            }
            $participant[] = [
                'id' => $key,
                'tournament_id' => $tournament_id,
                'name' => $player
            ];
        }
        return $participant;
    }
    private function getGroupObject(array $stage): array
    {
        $group = [];

        $numberOfGroups = $this->getNumberOfGroups($stage);

        for ($i = 0; $i < $numberOfGroups; $i++) {
            $group[] = [
               'id' => $i,
               'stage_id' => $stage['id'],
               'number' => $i + 1,
           ];
        }
        return $group;
    }
    private function getNumberOfGroups(array $stage): int
    {
        if ($stage['type'] == 'single_elimination') {
            if ($stage['settings']['consolationFinal']) {
                return 2;
            }
            return 1;
        } elseif ($stage['type'] == 'double_elimination') {
            if ($stage['settings']['consolationFinal']) {
                return 3;
            }
            return 2;
        }
        return 0;
    }
    private function getRoundObject(array $stage): array
    {
        $round = [];
        $numberOfRounds = $this->getNumberOfRounds($stage);

        if ($stage['type'] == 'single_elimination') {
            for ($i = 0; $i < $numberOfRounds; $i++) {
                $round[] = getSingleRoundObject($i, $stage['id'], $stage['group'][0]['id']);

                if ($stage['settings']['consolationFinal'] && $i === $numberOfRounds - 1) {
                    $round[] = getSingleRoundObject($i, $stage['id'], $stage['group'][1]['id']);
                }
            }
        }
        return $round;
    }
    private function getNumberOfRounds(array $stage): int
    {
        $playersCount = isset($stage['participant']) ? count($stage['participant']) : count($stage['seeding']);
        if ($stage['type'] == 'single_elimination') {
            if ($stage['settings']['consolationFinal']) {
                return ceil(log($playersCount, 2)) + 1;
            }
            return ceil(log($playersCount, 2));
        }
        return 0;
    }
    private function getMatchObject(array $stage): array
    {
        $round = [];

        if ($stage['type'] == 'single_elimination') {
            $seeding = $stage['seeding'];
            $numberOfMatches = $this->getNumberOfMatches(count($seeding));
            $numberOfRounds = $this->getNumberOfRounds($stage);

            $seeding = array_chunk(array_keys($stage['seeding']), 2);

            $matches = [];
            for ($round = 1; $round <= $numberOfRounds; $round++) {
                $matchCount = pow(2, $numberOfRounds - $round);
                for ($n = 1; $n <= $matchCount; $n++) {
                    $opponents = getOpponentObject($seeding, $stage['seeding']);
                    dd($opponents, $stage['seeding']);
                    $matches[] = getSingleMatchObject($round, $stage['id'], $stage['group'][1]['id'], $opponents);
                }
                if ($stage['settings']['consolationFinal'] && ($round === $numberOfMatches)) {
                    $matches[] = getSingleMatchObject($round, $stage['id'], $stage['group'][1]['id'], $opponent1, $opponent2);
                }
            }
            return $matches;
        }
        return [];
    }
    private function getNumberOfMatches(int $n): int
    {
        // if number is power of 2 then return as it is if not then next power of 2 number
        return  nextPowerOfTwo($n) - 1;

    }
    private function getMatchNumber(int $n): int
    {
        return intdiv($n, 2);
    }
}
