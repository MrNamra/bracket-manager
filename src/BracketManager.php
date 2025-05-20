<?php

namespace MrNamra\BracketManager;

use MrNamra\BracketManager\Interfaces\BracketManagerInterface;
class BracketManager {
    /**
     * @var array
     */ 
    private $setting;

    /**
     * @var array
     */ 

    /**
     * @var BracketManagerInterface
     */
    private $bracketManager;
    public function __construct(BracketManagerInterface $bracket_manager) {
        $this->bracketManager = $bracket_manager;
    }
    public function players(array $players) : void
    {
        try{
            $this->bracketManager->registerPlayers($players);
        } catch(\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }
    public function settings(array $settings) : void
    {
    }
    public function generateBracket(){
    }
}