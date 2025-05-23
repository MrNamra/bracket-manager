<?php

namespace MrNamra\BracketManager\Repository;

use MrNamra\BracketManager\Interfaces\ObjectCreatorInterface;

class ObjectCreatorRepository implements ObjectCreatorInterface
{
    private $object;
    public function getBracketObject(array $stage): array
    {
        $this->object['stage'][] = [
            'id' => $stage['id'],
            'tournament_id' => $stage['tournament_id'],
            'number' => $stage['number'],
            'name' => $stage['name'],
            'type' => $stage['type'],
            'settings' => $stage['settings'],
        ];

        $this->object['stage'][0]['settings']['size'] = nextPowerOfTwo($this->object['stage'][0]['settings']['size']);

        $this->object['participant'] = $this->getParticipantObject($stage['seeding'], $stage['tournament_id']);
        $this->object['group'] = $this->getGroupObject($stage);
        $this->object['round'] = $this->getRoundObject($this->object);
        $this->object['match'] = $this->getMatchObject($stage, $this->object['group']);

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
            if (isset($stage['settings']['consolationFinal']) && $stage['settings']['consolationFinal']) {
                return 2;
            }
            return 1;
        } elseif ($stage['type'] == 'double_elimination') {
            if (isset($stage['settings']['consolationFinal']) && $stage['settings']['consolationFinal']) {
                return 3;
            }
            return 2;
        }
        return 0;
    }
    private function getRoundObject(array $stage): array
    {
        $round = [];
        $stageData = $stage['stage'][0];
        $numberOfRounds = $this->getNumberOfRounds($stageData);

        if ($stageData['type'] == 'single_elimination') {
            for ($i = 0; $i < $numberOfRounds; $i++) {
                $round[] = getSingleRoundObject($i, $stageData['id'], $stage['group'][0]['id']);

                if ((isset($stage['settings']['consolationFinal']) && $stageData['settings']['consolationFinal']) && $i === $numberOfRounds - 1) {
                    $round[] = getSingleRoundObject($i, $stageData['id'], $stage['group'][1]['id']);
                }
            }
        }
        return $round;
    }
    private function getNumberOfRounds(array $stage): int
    {
        $playersCount = isset($stage['settings']['size']) ? nextPowerOfTwo($stage['settings']['size']) : count($stage['seeding']);
        if ($stage['type'] == 'single_elimination') {
            if (isset($stage['settings']['consolationFinal']) && $stage['settings']['consolationFinal']) {
                return ceil(log($playersCount, 2)) + 1;
            }
            return ceil(log($playersCount, 2));
        }
        return 0;
    }
    private function getMatchObject(array $stage, array $group): array
    {
        $round = [];

        if ($stage['type'] == 'single_elimination') {
            $seeding = $stage['seeding'];
            $numberOfMatches = $this->getNumberOfMatches(count($seeding));
            $numberOfRounds = $this->getNumberOfRounds($stage);

            $seeding = array_chunk(array_keys($stage['seeding']), 2);

            $matches = [];
            $id = 0;
            for ($round = 1; $round <= $numberOfRounds; $round++) {
                $matchCount = pow(2, $numberOfRounds - $round);
                $number = 1;
                $position = null;
                if ($round == 1) {
                    $position = $number;
                } else {
                    $position = null;
                }
                for ($n = 1; $n <= $matchCount; $n++) {
                    $opponents = getOpponentObject($seeding, $stage['seeding'], $position);
                    $matches[] = getSingleMatchObject($id++, $number, $stage['id'], $group[0]['id'], $round - 1, $opponents);
                    unset($seeding[0]);
                    $seeding = array_values($seeding);
                    $number++;
                    $position += 2;
                }
                if ((isset($stage['settings']['consolationFinal']) && $stage['settings']['consolationFinal']) && ($round === $numberOfMatches)) {
                    $matches[] = getSingleMatchObject($id++, $number++, $stage['id'], $group[1]['id'], 1, $opponents);
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
