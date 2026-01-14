<?php

declare(strict_types=1);

namespace Seat\SpyDetection\DTO;

class SpyContext
{
    public int $seatUserId;
    /** @var int[] */
    public array $characterIds;
    public int $inputCharacterId;
    public string $inputCharacterName;
    public int $requestedByUserId;

    /** @var array<int, array<string, mixed>> */
    public array $walletJournals = [];
    /** @var array<int, array<string, mixed>> */
    public array $assets = [];
    /** @var array<int, array<string, mixed>> */
    public array $corpHistory = [];
    /** @var array<int, array<string, mixed>> */
    public array $characters = [];

    /**
     * @param int[] $characterIds
     * @param array<int, array<string, mixed>> $walletJournals
     * @param array<int, array<string, mixed>> $assets
     * @param array<int, array<string, mixed>> $corpHistory
     * @param array<int, array<string, mixed>> $characters
     */
    public function __construct(
        int $seatUserId,
        array $characterIds,
        int $inputCharacterId,
        string $inputCharacterName,
        int $requestedByUserId,
        array $walletJournals = [],
        array $assets = [],
        array $corpHistory = [],
        array $characters = []
    ) {
        $this->seatUserId = $seatUserId;
        $this->characterIds = $characterIds;
        $this->inputCharacterId = $inputCharacterId;
        $this->inputCharacterName = $inputCharacterName;
        $this->requestedByUserId = $requestedByUserId;
        $this->walletJournals = $walletJournals;
        $this->assets = $assets;
        $this->corpHistory = $corpHistory;
        $this->characters = $characters;
    }
}
