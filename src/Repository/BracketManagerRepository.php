<?php

namespace MrNamra\BracketManager\Repository;

use Illuminate\Database\Eloquent\Casts\Json;
use MrNamra\BracketManager\Interfaces\BracketManagerInterface;
use MrNamra\BracketManager\Interfaces\ObjectCreatorInterface;
use MrNamra\BracketManager\Interfaces\SeedingManagerInterface;

class BracketManagerRepository implements BracketManagerInterface
{
    /**
     * @var object
     */
    private $seeding;

    /**
     * @var object
     */
    private $objectCreator;

    public function __construct(SeedingManagerInterface $seeding, ObjectCreatorInterface $objectCreator)
    {
        $this->seeding = $seeding;
        $this->objectCreator = $objectCreator;
    }
    public function create(array $stage): string
    {
        // validate data
        $stage = validateStage($stage);

        if ($stage['type'] == 'single_elimination' || $stage['type'] == 'double_elimination') {
            $stage['seeding'] = $this->getSeeding($stage);
        }

        $matchObject = $this->objectCreator->getBracketObject($stage);

        $matchObject['match_game'] = [];
        return Json::encode($matchObject);
    }
    private function getSeeding(array $stage): array
    {
        $stageType = $stage['settings']['seedOrdering'][0];
        switch ($stageType) {
            case 'natural':
                $seedingData = $this->seeding->getNeturalSeeding(array_values($stage['seeding']));
                $stage['settings']['size'] = $seedingData['size'];

                return $seedingData['paticipents'];

            case 'reverse':
                $seedingData = $this->seeding->getReverseSeeding(array_values($stage['seeding']));
                $stage['settings']['size'] = $seedingData['size'];

                return $seedingData['paticipents'];

            case 'half_shift':
                $seedingData = $this->seeding->getHalfShiftSeeding(array_values($stage['seeding']));
                $stage['settings']['size'] = $seedingData['size'];

                return $seedingData['paticipents'];

            case 'reverse_half_shift':
                $seedingData = $this->seeding->getReverseHalfShiftSeeding(array_values($stage['seeding']));
                $stage['settings']['size'] = $seedingData['size'];

                return $seedingData['paticipents'];

            case 'pair_flip':
                $seedingData = $this->seeding->getPairFlipSeeding(array_values($stage['seeding']));
                $stage['settings']['size'] = $seedingData['size'];

                return $seedingData['paticipents'];

            case 'inner_outer':
                $seedingData = $this->seeding->getInnerOuterSeeding(array_values($stage['seeding']));
                $stage['settings']['size'] = $seedingData['size'];

                // no break
            case 'half_shift_inner_outer':
                $seedingData = $this->seeding->getHalfShiftInnerOuterSeeding(array_values($stage['seeding']));
                $stage['settings']['size'] = $seedingData['size'];

                return $seedingData['paticipents'];

            case "double_elimination":
                return getBracketSeeding($stage['seeding']);
        }
        return [];
    }
    public function update(array $matchData, array $score): array
    {
        $matchObject = $this->objectCreator->addScore($matchData, $score);
        return $matchObject;
    }

    public function mapPlayerId()
    {
        @trigger_error('This is under development. Usecase: by this fucntion you can assign player/team id', E_USER_ERROR);
    }
}
