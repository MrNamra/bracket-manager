<?php

namespace MrNamra\BracketManager\Repository;

use Illuminate\Database\Eloquent\Casts\Json;
use MrNamra\BracketManager\Interfaces\BracketManagerInterface;
use Mrnamra\BracketManager\Repository\SeedingManager;

class BracketManagerRepository implements BracketManagerInterface
{
    private $seeding;
    public function __construct(SeedingManager $seeding)
    {
        $this->seeding = $seeding;
    }
    public function create(array $stage): object
    {
        // validate data
        validateStage($stage);

        $seeding = $stage['seeding'];
        if ($stage['type'] == 'single_elimination' || $stage['type'] == 'double_elimination') {
            $stage['seeding'] = $this->getSeeding($stage);
            dd($stage);
        }
        return Json::encode($stage);
    }
    private function getSeeding(array $stage): array
    {
        $stageType = $stage['settings']['seedOrdering'][0];
        switch ($stageType) {
            case 'natural':
                return $this->seeding->getNeturalSeeding(array_values($stage['seeding']));
            case "double_elimination":
                return getBracketSeeding($stage['seeding']);
        }
        return [];
    }

    public function mapPlayerId()
    {
        @trigger_error('This is under development by this fucntion you can assign player/team id', E_USER_ERROR);
        dd("ewew");
    }
}
