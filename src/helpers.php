<?php

function validateStage(array $stage): array
{
    // 1. tournament_id: required, must be int
    if (!array_key_exists('tournament_id', $stage) || !is_int($stage['tournament_id'])) {
        throw new \InvalidArgumentException("Field 'tournament_id' is required and must be an integer.");
    }

    // 2. stageId: optional, if present must be int
    if (isset($stage['id'])) {
        if (!is_int($stage['id'])) {
            throw new \InvalidArgumentException("Field 'id' must be an integer if present.");
        }
    } else {
        $stage['id'] = 0;
    }

    if (!array_key_exists('name', $stage)) {
        $stage['name'] = ' ';
    }
    if (array_key_exists('name', $stage) && $stage['name'] === '') {
        $stage['name'] = ' ';
    }

    // 4. type: required, must be one of the allowed enums
    $validTypes = ['single_elimination', 'double_elimination'];
    if (!array_key_exists('type', $stage) || !in_array($stage['type'], $validTypes, true)) {
        throw new \InvalidArgumentException("Field 'type' is required and must be one of: " . implode(', ', $validTypes));
    }

    // 5. seeding: required, array with at least 2 items
    if (!array_key_exists('seeding', $stage) || !is_array($stage['seeding']) || count($stage['seeding']) < 2) {
        throw new \InvalidArgumentException("Field 'seeding' is required and must be an array with at least 2 items.");
    }

    // 6. settings: required, must be array
    if (!array_key_exists('settings', $stage) || !is_array($stage['settings'])) {
        throw new \InvalidArgumentException("Field 'settings' is required and must be an array.");
    }

    // 6. settings: required, must be array
    if (array_key_exists('number', $stage) && !is_integer($stage['number'])) {
        throw new \InvalidArgumentException("Field 'number' is required and must be an integer if present.");
    } else {
        $stage['number'] = 1;
    }

    $settings = $stage['settings'];

    // 7. settings.size: required, must be int
    if (!array_key_exists('size', $settings) || !is_int($settings['size'])) {
        throw new \InvalidArgumentException("Field 'settings.size' is required and must be an integer.");
    }

    // 8. settings.seedOrdering: required, array with at least 1 item, each must be a valid enum
    if (array_key_exists('consolationFinal', $settings) && !is_bool($settings['consolationFinal'])) {
        throw new \InvalidArgumentException("Field 'settings.consolationFinal' must boolean if present");
    }

    // 8. settings.seedOrdering: required, array with at least 1 item, each must be a valid enum
    $validSeedOrderings = ['natural', 'reverse', 'half_shift', 'reverse_half_shift', 'pair_flip', 'inner_outer', 'half_shift_inner_outer'];
    if (!array_key_exists('seedOrdering', $settings) || !is_array($settings['seedOrdering']) || count($settings['seedOrdering']) < 1) {
        throw new \InvalidArgumentException("Field 'settings.seedOrdering' is required and must be a non-empty array.");
    }
    foreach ($settings['seedOrdering'] as $ordering) {
        if (!in_array($ordering, $validSeedOrderings, true)) {
            throw new \InvalidArgumentException("Invalid value in 'settings.seedOrdering': $ordering. Allowed: " . implode(', ', $validSeedOrderings));
        }
    }

    // 5. seeding: same as size
    if (count($stage['seeding']) !== $settings['size']) {
        throw new \InvalidArgumentException("Total number of participants is equal to stage size");
    }

    // 9. settings.grandFinal: required, must be one of the allowed enums
    $validGrandFinal = ['single', 'double'];
    if (!array_key_exists('grandFinal', $settings) || !in_array($settings['grandFinal'], $validGrandFinal, true)) {
        throw new \InvalidArgumentException("Field 'settings.grandFinal' is required and must be one of: " . implode(', ', $validGrandFinal));
    }

    // 10. settings.matchesChildCount: required, must be int
    if (!array_key_exists('matchesChildCount', $settings) || !is_int($settings['matchesChildCount'])) {
        throw new \InvalidArgumentException("Field 'settings.matchesChildCount' is required and must be an integer.");
    }

    // 11. single_elimination: required atlest 2 opponet
    if ($stage['type'] === 'single_elimination') {
        if (count($stage['seeding']) < 2 || $stage['settings']['size'] < 2) {
            throw new \InvalidArgumentException("To create `Single Elimination` stage then atleast 2 playes");
        } else {
            if ($settings['grandFinal'] == 'double') {
                throw new \InvalidArgumentException("Can't create ");
            }
        }

    }

    // 11. double_elimination: required atlest 2 opponet
    if ($stage['type'] === 'double_elimination' && (count($stage['seeding']) < 4 || $stage['settings']['size'] < 4)) {
        throw new \InvalidArgumentException("To create `Double Elimination` stage then atleast 4 playes");
    }

    return $stage;
}
function validateMatch(array $match)
{
    $match = array_values($match)[0];
    if ($match['status'] !== 2) {
        throw new \InvalidArgumentException("Match is lock.");
    }
    if (empty($match['opponent1']) || empty($match['opponent2'])) {
        throw new \InvalidArgumentException("Invalid Match.");
    }
    if ($match['opponent1']['id'] == null || $match['opponent2']['id'] == null) {
        throw new \InvalidArgumentException("Invalid Match.");
    }
    return true;
}
function getBracketSeeding(array $playes): array
{
    $playes = array_values($playes);
    dd(singleEliminationAlgorithm(count($playes)));
    // finalSingleEliminationAlgorithm();


    return [];
}

function singleEliminationAlgorithm($numPlayers, $showBrackets = false)
{
    if ($numPlayers < 2) {
        return [];
    }
    $rounds = ceil(log($numPlayers, 2));
    $bracketSize = pow(2, $rounds);

    $seeds = generateBracket(substr($bracketSize, 0));
    dd($seeds);

    $matches = [];
    for ($i = 0; $i < $bracketSize; $i += 2) {
        $seed1 = $seeds[$i] <= $numPlayers ? $seeds[$i] : null;
        $seed2 = $seeds[$i + 1] <= $numPlayers ? $seeds[$i + 1] : null;
        $matches[] = $seed1;
        $matches[] = $seed2;
    }

    if ($showBrackets) {
        return $matches;
    }

    $chunks = array_chunk($matches, 2);
    for ($i = 0; $i < count($matches); $i += 2) {
        if ((isset($matches[$i]) && $matches[$i][1] === null) && (isset($matches[$i + 1]) && $matches[$i + 1][0] === null)) {
            $combined = array_merge($matches[$i], $matches[$i + 1]);
            if (array_filter($combined, fn ($value) => $value !== null)) {
                $filtered = array_values(array_filter($combined, fn ($value) => $value !== null));
                $a[1][] = [$filtered[0] ?? null, $filtered[1] ?? null];
            } else {
                $a[1][] = [null, null];
            }
        } else {
            if (in_array(null, $matches[$i])) {
                $a[1][] = $matches[$i];
            } else {
                $a[0][] = $matches[$i];
            }

            if (isset($matches[$i + 1]) && in_array(null, $matches[$i + 1])) {
                $a[1][] = $matches[$i + 1];
            } elseif (isset($matches[$i + 1]) && !in_array(null, $matches[$i + 1])) {
                $a[0][] = $matches[$i + 1];
            }
        }
    }
    $rounds = [];
    foreach ($chunks as $index => $pair) {
        $round1 = [];
        $round2 = [];
        foreach ($pair as $matchNo => $match) {
            if (in_array(null, $match)) {
                if ($match[0] == null) {
                    $round2[$index][] = $match[1];
                } else {
                    $round2[$index][] = $match[0];
                }
            } else {
                if ($numPlayers > 2) {
                    $round2[$index][] = null;
                } else {
                    unset($round2);
                }
                $round1[] = $match;
            }
        }
        if (!isset($rounds[0])) {
            $rounds[0] = [];
        }
        if (!isset($rounds[1]) && $numPlayers > 2) {
            $rounds[1] = [];
        }
        if (isset($round1)) {
            $rounds[0] = array_merge($rounds[0], $round1);
        }
        if (isset($round2)) {
            $rounds[1] = array_merge($rounds[1], $round2);
        }
    }
    return $rounds;
}
// 1-1 2-1
function generateBracket($n)
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
function isPowerOfTwo(int $n): bool
{
    return ($n & ($n - 1)) == 0;
}
function nextPowerOfTwo(int $n): int
{
    return pow(2, ceil(log($n, 2)));
}
function GenrateAllRounds($remainingMatches, $originalMatches = [], $index = 0)
{
    $roundGen = $remainingMatches / 2;
    for ($i = 0; $i < $roundGen; $i++) {
        $originalMatches[$index][] = [
            'opponent1' => ['id' => null],
            'opponent2' => ['id' => null],
        ];
    }

    if ($roundGen > 1) {
        return GenrateAllRounds($roundGen, $originalMatches, $index + 1);
    } else {
        return ($originalMatches);
    }
}
function getSingleRoundObject(int $i, int $id, int $group_id): array
{
    return [
        'id' => $i,
        'number' => $i + 1,
        'stage_id' => $id,
        'group_id' => $group_id,
    ];
}
function getSingleMatchObject(int $id, int $number, int $stage_id, int $group_id, int $round_id, array $opponents): array
{
    $status = 0;
    if ($opponents[0] === null && $opponents[1] !== null) {
        $opponents[1]['result'] = 'win';
        $status = 0;
    } elseif ($opponents[0] !== null && $opponents[1] === null) {
        $opponents[0]['result'] = 'win';
        $status = 1;
    } else {
        $status = 2;
    }

    return [
        'id' => $id,
        'number' => $number,
        'stage_id' => $stage_id,
        'group_id' => $group_id,
        'round_id' => $round_id,
        'child_count' => 0,
        'status' => $status,
        'opponent1' => $opponents[0],
        'opponent2' => $opponents[1]
    ];
}
function getOpponentObject(array $seeds, array $seeding, int $position = null): array
{
    $opponent = [];
    if (isset($seeds[0])) {
        foreach ($seeds[0] as $seed) {
            if ($seeding[$seed] !== null) {
                $entry = [
                    'id' => $seed
                ];
                if ($position !== null) {
                    $entry['position'] = $position++;
                }
                $opponent[] = $entry;
            } else {
                $opponent[] = null;
            }
        }
    } else {
        $opponent[] = [
            'id' => null
        ];
        $opponent[] = [
            'id' => null
        ];
    }
    return $opponent;
}
function changeIntoBye($seed, $participantsCount)
{
    return $seed <= $participantsCount ? $seed : null;
}
function generateMinorOrdering(int $numPlayers, array $userInput = []): array
{
    // Known patterns for specific tournament sizes:
    $patterns = [
        8   => ['natural', 'reverse', 'normal'],
        16  => ['natural', 'reverse_half_shift', 'reverse', 'normal'],
        32  => ['natural', 'reverse', 'half_shift', 'normal', 'normal'],
        64  => ['natural', 'reverse', 'half_shift', 'reverse', 'normal', 'normal'],
        128 => ['normal', 'reverse', 'half_shift', 'pair_flip', 'pair_flip', 'pair_flip', 'normal'],
    ];

    // Fallback cycle pattern for extending beyond known rounds or large tournaments
    $fallback = ['reverse', 'half_shift', 'reverse_half_shift', 'pair_flip', 'natural'];

    $totalRounds = (int)(2 * log($numPlayers, 2) - 1);
    $minorRounds = (int) ceil($totalRounds / 2);

    if (isset($patterns[$numPlayers])) {
        $baseOrdering = $patterns[$numPlayers];
        while (count($baseOrdering) < $minorRounds) {
            $index = (count($baseOrdering) - count($patterns[$numPlayers])) % count($fallback);
            $baseOrdering[] = $fallback[$index];
        }
    } else {
        $baseOrdering = $patterns[128];
        while (count($baseOrdering) < $minorRounds) {
            $index = (count($baseOrdering) - count($patterns[128])) % count($fallback);
            $baseOrdering[] = $fallback[$index];
        }
    }

    $result = [];

    for ($i = 0; $i < $minorRounds; $i++) {
        if (isset($userInput[$i])) {
            $result[] = $userInput[$i];
        } else {
            $result[] = $baseOrdering[$i];
        }
    }

    return $result;
}
function applySeeding(array $participants, string $method): array
{
    // Extract non-null entries with their indexes
    $indexed = array_filter($participants, fn ($p) => $p !== null);
    $nonNullValues = array_values($indexed);
    $nonNullCount = count($nonNullValues);

    // Prepare ordered indexes
    $indexes = range(0, $nonNullCount - 1);

    // Apply pattern to indexes
    switch ($method) {
        case 'reverse':
            $transformedIndexes = array_reverse($indexes);
            break;
        case 'half_shift':
            $half = (int)ceil($nonNullCount / 2);
            $transformedIndexes = array_merge(
                array_slice($indexes, $half),
                array_slice($indexes, 0, $half)
            );
            break;
        case 'reverse_half_shift':
            $half = (int)ceil($nonNullCount / 2);
            $transformedIndexes = array_merge(
                array_reverse(array_slice($indexes, $half)),
                array_reverse(array_slice($indexes, 0, $half))
            );
            break;
        case 'pair_flip':
            $transformedIndexes = [];
            for ($i = 0; $i < $nonNullCount; $i += 2) {
                if (isset($indexes[$i + 1])) {
                    $transformedIndexes[] = $indexes[$i + 1];
                    $transformedIndexes[] = $indexes[$i];
                } else {
                    $transformedIndexes[] = $indexes[$i];
                }
            }
            break;
        case 'natural':
        default:
            $transformedIndexes = $indexes;
            break;
    }

    // Apply transformed values into new array, preserving nulls
    $result = $participants;
    $i = 0;
    foreach ($participants as $key => $val) {
        if ($val !== null) {
            $result[$key] = $nonNullValues[$transformedIndexes[$i++]];
        }
    }

    return $result;
}
