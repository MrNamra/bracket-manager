<?php

namespace MrNamra\BracketManager\Repository;

use Illuminate\Database\Eloquent\Casts\Json;
use MrNamra\BracketManager\Interfaces\BracketManagerInterface;
use MrNamra\BracketManager\Interfaces\ObjectCreatorInterface;
use MrNamra\BracketManager\Interfaces\SeedingManagerInterface;

class BracketManagerRepository implements BracketManagerInterface
{
    private $seeding;

    private $objectCreator;

    public function __construct(SeedingManagerInterface $seeding, ObjectCreatorInterface $objectCreator)
    {
        $this->seeding = $seeding;
        $this->objectCreator = $objectCreator;
    }
    public function create(array $stage): string
    {
        // validate data
        validateStage($stage);

        $seeding = $stage['seeding'];
        if ($stage['type'] == 'single_elimination' || $stage['type'] == 'double_elimination') {
            $stage['seeding'] = $this->getSeeding($stage);
        }

        $matchObject = $this->objectCreator->getBracketObject($stage);
        dd($matchObject);

        $matchObject['match_game'] = [];
        dd($matchObject);
        return Json::encode($stage);
    }
    private function getSeeding(array $stage): array
    {
        $stageType = $stage['settings']['seedOrdering'][0];
        switch ($stageType) {
            case 'natural':
                $seedingData = $this->seeding->getNeturalSeeding(array_values($stage['seeding']));
                $stage['settings']['size'] = $seedingData['size'];

                return $seedingData['paticipents'];

            case "double_elimination":
                return getBracketSeeding($stage['seeding']);
        }
        return [];
    }
    private function getPaticipentsObject()
    {

    }

    public function mapPlayerId()
    {
        @trigger_error('This is under development. Usecase: by this fucntion you can assign player/team id', E_USER_ERROR);
    }
}
