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
        $this->object['match'] = $this->getUpperMatchObject($stage, $this->object['group']);
        if ($stage['type'] == 'double_elimination') {
            $this->object['match'] = $this->getLowerMatchObject($this->object);
        }

        return $this->object;
    }
    public function addScore(array $brackectObj, array $score): array
    {
        $brackectObj = $this->updateScore($brackectObj, $score);
        $brackectObj['match'] = $this->pushWinnerToNextRound($brackectObj['match'], 0);
        return $brackectObj;
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

        if ($stageData['type'] == 'single_elimination' || $stageData['type'] == 'double_elimination') {
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
        } elseif ($stage['type'] == 'double_elimination') {
            $totalWBRounds = (int)log($playersCount, 2);
            $totalLBRounds = ($totalWBRounds - 1) * 2 + 1;
            return $totalWBRounds + $totalLBRounds;
        }
        return 0;
    }
    private function getUpperMatchObject(array $stage, array $group): array
    {
        $round = [];

        $seeding = $stage['seeding'];
        $numberOfMatches = $this->getNumberOfMatches(count($seeding));
        $numberOfRounds = $this->getNumberOfRounds($stage);

        if ($stage['type'] == 'double_elimination') {
            $numberOfRounds = (int)log(count($seeding), 2);
        }

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
        $matches = $this->pushWinnerToNextRound($matches, 0);
        return $matches;
    }
    private function getNumberOfMatches(int $n): int
    {
        // if number is power of 2 then return as it is if not then next power of 2 number
        return  nextPowerOfTwo($n) - 1;

    }
    private function pushWinnerToNextRound(array $matches, $currentRound): array
    {
        $round1Matches = array_filter($matches, function ($match) use ($currentRound) {
            return ($match['round_id'] == $currentRound);
        });
        $round2Matches = array_filter($matches, function ($match) use ($currentRound) {
            return $match['round_id'] == $currentRound + 1;
        });
        if (empty($round2Matches)) {
            return $matches;
        }

        $round1Keys = array_keys($round1Matches);
        $round2Keys = array_keys($round2Matches);

        for ($i = 0; $i < count($round1Matches); $i++) {
            $key = $round1Keys[$i];
            $match = $round1Matches[$key];
            if (empty($match['opponent1']) && empty($match['opponent2'])) {
                $slot = $i / 2;
                if (gettype($slot) == 'integer') {
                    $tmpI = $round2Keys[$slot];
                    $matches[$tmpI]['opponent1'] = null;
                } else {
                    $tmpI = $round2Keys[$slot];
                    $matches[$tmpI]['opponent2'] = null;
                }
            } elseif (isset($match['opponent1']['result'])) {
                $slot = $i / 2;
                $winner = ($match['opponent1']['result'] == 'win') ?
                            $match['opponent1']['id'] :
                            $match['opponent2']['id'];
                if (gettype($slot) == 'integer') {
                    $tmpI = $round2Keys[$slot];
                    $matches[$tmpI]['opponent1']['id'] = $winner;
                } else {
                    $tmpI = $round2Keys[$slot];
                    $matches[$tmpI]['opponent2']['id'] = $winner;
                }
            } elseif (isset($match['opponent2']['result'])) {
                $slot = $i / 2;
                $winner = ($match['opponent2']['result'] == 'win') ?
                            $match['opponent2']['id'] :
                            $match['opponent1']['id'];
                if (gettype($slot) == 'integer') {
                    $tmpI = $round2Keys[$slot];
                    $matches[$tmpI]['opponent1']['id'] = $winner;
                } else {
                    $tmpI = $round2Keys[$slot];
                    $matches[$tmpI]['opponent2']['id'] = $winner;
                }
            }
        }
        $round2Matches = array_filter($matches, function ($match) use ($currentRound) {
            return $match['round_id'] == $currentRound + 1;
        });
        foreach ($round2Matches as $key => $match) {
            if (empty($match['opponent1'])) {
                $matches[$key]['opponent2']['result'] = 'win';
            } elseif (empty($match['opponent2'])) {
                $matches[$key]['opponent1']['result'] = 'win';
            }
        }
        return $this->pushWinnerToNextRound($matches, $currentRound + 1);
    }
    private function updateScore(array $brackectObj, array $score): array
    {

        $match = array_filter($brackectObj['match'], function ($match) use ($score) {
            return $match['id'] == $score['id'];
        });
        validateMatch($match);
        $key = array_keys($match)[0];
        $match = array_values($match)[0];

        // Handle opponent1
        $match['opponent1']['score'] = $score['opponent1']['score'];
        $match['opponent1']['result'] = $score['opponent1']['result'] ?? 'loss';

        // Handle opponent2
        $match['opponent2']['score'] = $score['opponent2']['score'];
        $match['opponent2']['result'] = $score['opponent2']['result'] ?? 'loss';


        $brackectObj['match'][$key] = $match;
        return $brackectObj;
    }
    private function getLowerMatchObject(array $stage): array
    {
        $totalPlayers = $stage['stage'][0]['settings']['size'];
        $stageId = $stage['stage'][0]['id'];

        $totalWBRounds = (int)log($totalPlayers, 2);
        $totalLBRounds = ($totalWBRounds - 1) * 2 + 1;

        // Create match entries for each LB round
        $matchId = $this->getNextMatchId($stage['match']);
        // $roundIdOffset = $stage['round'][$totalWBRounds]['id'];
        $roundId = $stage['round'][$totalWBRounds]['id'];

        // Generate LB round structure
        $roundWB = 0;
        for ($i = 0; $i < $totalLBRounds; $i++) {
            $k = 0;
            $position = 1;

            $matchesInRound = $this->getLBMatchesCount($i, $totalPlayers);

            for ($j = 0; $j < $matchesInRound; $j++) {
                $opponents = [];
                $matchesWB = array_values(array_filter($stage['match'], function ($match) use ($roundWB) {
                    return $match['round_id'] == $roundWB;
                }));
                $matchesLB = array_values(array_filter($stage['match'], function ($match) use ($roundId) {
                    return ($match['round_id'] == $roundId - 1 && $match['group_id'] == 1);
                }));
                if ($i == 0) {
                    $match1 = $matchesWB[$k];
                    $match2 = $matchesWB[$k + 1];

                    $opponents[0]['position'] = $position++;
                    $opponents[1]['position'] = $position++;

                    $k += 2;
                } else if($i % 2 !== 0) {
                    $match1 = $matchesWB[$k];
                    $match2 = $matchesLB[$k];

                    $opponents[0]['position'] = $position++;
                    // $opponents[1]['position'] = $position++;

                    $k++;
                } else {
                    $opponents[0]['id'] = null;
                    $opponents[1]['id'] = null;
                }

                if ($i == 0 && (!empty($matchesWB) || !empty($matchesLB))) {
                    if (empty($match1['opponent1']) || empty($match1['opponent2'])) {
                        $opponents[0] = null;
                    } else {
                        $opponents[0]['id'] = null;
                    }

                    if (empty($match2['opponent1']) || empty($match2['opponent2'])) {
                        $opponents[1] = null;
                        !empty($opponents[0]) ? $opponents[0]['result'] = 'win' : null;
                    } else {
                        $opponents[1]['id'] = null;
                        !empty($opponents[0]) ? $opponents[1]['result'] = 'win' : null;
                    }
                } else {
                    $opponents[0]['id'] = null;
                    $opponents[1]['id'] = null;
                }
                $stage['match'][] = getSingleMatchObject($matchId++, $j + 1, $stageId, 1, $roundId, $opponents);
                // if($i == 1) dd($stage['match']);

            }
            $roundId = $roundId + 1;
            
            if($i===0 || $i % 2 !== 0){
                $roundWB++;
            }
        }
        return $stage['match'];
    }
    private function getNextMatchId(array $matches): int
    {
        return collect($matches)->max('id') + 1;
    }
    private function getLBMatchesCount(int $roundIndex, int $totalPlayers): int
    {
        $totalWBRounds = (int) log($totalPlayers, 2);
        $totalLBRounds = ($totalWBRounds - 1) * 2 + 1;

        if ($roundIndex === 0) {
            return $totalPlayers / 4;
        }

        if ($roundIndex % 2 !== 0) {
            return $this->getLBMatchesCount($roundIndex - 1, $totalPlayers);
        }

        return $this->getLBMatchesCount($roundIndex - 2, $totalPlayers) / 2;
    }
}
