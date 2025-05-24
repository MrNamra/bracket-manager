<?php

namespace MrNamra\BracketManager\Interfaces;

interface BracketManagerInterface
{
    public function create(array $satage): string;
    public function update(array $matchData, array $score): array;
    public function mapPlayerId();
}
