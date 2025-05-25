<?php

namespace MrNamra\BracketManager\Interfaces;

interface SeedingManagerInterface
{
    public function getNeturalSeeding(array $seeding): array;
    public function getReverseSeeding(array $paticipents): array;
    public function getHalfShiftSeeding(array $paticipents, bool $return = false): array;
    public function getReverseHalfShiftSeeding(array $paticipents): array;
    public function getPairFlipSeeding(array $participants): array;
    public function getInnerOuterSeeding(array $participants): array;
    public function getHalfShiftInnerOuterSeeding(array $participants): array;
}
