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
        }
        return 0;
    }
    private function getRoundObject(array $stage): array
    {
        $round = [];
        $numberOfRounds = $this->getNumberOfRounds($stage);

        if ($stage['type'] == 'single_elimination') {
            if ($stage['settings']['consolationFinal']) {
                for ($i = 0; $i < $numberOfRounds; $i++) {
                    if ($i === $numberOfRounds - 1) {
                        $round[] = [
                            'id' => $i,
                            'number' => $i + 1,
                            'stage_id' => $stage['id'],
                            'group_id' => $stage['group'][1]['id'],
                        ];
                    } else {
                        $round[] = [
                            'id' => $i,
                            'number' => $i + 1,
                            'stage_id' => $stage['id'],
                            'group_id' => $stage['group'][0]['id'],
                        ];
                    }
                }
            } else {
                for ($i = 0; $i < $numberOfRounds; $i++) {
                    $round[] = [
                        'id' => $i,
                        'number' => $i + 1,
                        'stage_id' => $stage['id'],
                        'group_id' => $stage['id'],
                    ];
                }
            }
        }
        return $round;
    }
    private function getNumberOfRounds(array $stage): int
    {
        $playersCount = count($stage['participant']);
        if ($stage['type'] == 'single_elimination') {
            if ($stage['settings']['consolationFinal']) {
                return ceil(log($playersCount, 2)) + 1;
            }
            return ceil(log($playersCount, 2));
        }
        return 0;
    }
}
