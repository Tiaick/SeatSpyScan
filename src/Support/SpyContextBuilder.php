<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Support;

use RuntimeException;
use Seat\SpyDetection\DTO\SpyContext;
use Seat\SpyDetection\Resolvers\SeatCharacterResolver;

class SpyContextBuilder
{
    public function __construct(
        private readonly SeatCharacterResolver $resolver,
        private readonly SeatDataLoader $dataLoader
    ) {
    }

    public function buildFromCharacterName(string $characterName, int $requestedByUserId): SpyContext
    {
        $character = $this->resolver->findCharacterByName($characterName);
        if (!$character) {
            throw new RuntimeException('Character not found.');
        }

        $characterId = (int) ($character['character_id'] ?? 0);
        if ($characterId <= 0) {
            throw new RuntimeException('Character ID missing.');
        }

        $seatUserId = $this->resolver->findSeatUserIdForCharacter($characterId);
        if (!$seatUserId) {
            throw new RuntimeException('No SeAT user linked to character.');
        }

        $characterIds = $this->resolver->listCharacterIdsForSeatUser($seatUserId);
        if (empty($characterIds)) {
            $characterIds = [$characterId];
        }

        $data = $this->dataLoader->loadForCharacters($characterIds);

        return new SpyContext(
            $seatUserId,
            $characterIds,
            $characterId,
            $characterName,
            $requestedByUserId,
            $data['wallet_journals'] ?? [],
            $data['assets'] ?? [],
            $data['corp_history'] ?? [],
            $data['characters'] ?? []
        );
    }
}
