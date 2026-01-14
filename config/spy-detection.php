<?php

declare(strict_types=1);

return [
    // Negative entity lists (IDs). These are config only, no DB tables.
    'negative_character_ids' => [
        90000001,
        90000002,
    ],
    'negative_corporation_ids' => [
        1000169,
    ],
    'negative_alliance_ids' => [
        99000006,
    ],

    // Suspicious asset indicators.
    'suspicious_type_ids' => [
        1154,
        2028,
    ],
    'suspicious_group_ids' => [
        130,
    ],
    'suspicious_category_ids' => [
        6,
    ],

    // Scan behavior.
    'scan' => [
        'force_queue' => false,
        'queue_if_chars_gt' => 12,
        'wallet_journal_days' => 90,
        'wallet_journal_limit' => 1000,
        'asset_limit' => 2000,
        'corp_history_limit' => 100,
        'cache_ttl_minutes' => 30,
    ],

    // SeAT model bindings. Adjust if your install uses different classes.
    'models' => [
        'character' => \Seat\Eveapi\Models\Character\CharacterInfo::class,
        'wallet_journal' => \Seat\Eveapi\Models\Wallet\CharacterWalletJournal::class,
        'asset' => \Seat\Eveapi\Models\Asset\CharacterAsset::class,
        'corporation_history' => \Seat\Eveapi\Models\Character\CharacterCorporationHistory::class,
        'seat_user' => \Seat\Web\Models\User::class,
    ],

    // Column mappings for flexible schemas.
    'columns' => [
        'character_id' => 'character_id',
        'character_name' => 'name',
        'character_birthday' => 'birthday',
        'character_user_id' => 'user_id',
        'wallet' => [
            'character_id' => 'character_id',
            'amount' => 'amount',
            'date' => 'date',
            'first_party_id' => 'first_party_id',
            'second_party_id' => 'second_party_id',
            'ref_type' => 'ref_type',
            'reason' => 'reason',
        ],
        'asset' => [
            'character_id' => 'character_id',
            'type_id' => 'type_id',
            'group_id' => 'group_id',
            'category_id' => 'category_id',
            'quantity' => 'quantity',
            'location_id' => 'location_id',
            'location_type' => 'location_type',
        ],
        'corp_history' => [
            'character_id' => 'character_id',
            'corporation_id' => 'corporation_id',
            'alliance_id' => 'alliance_id',
            'start_date' => 'start_date',
        ],
    ],

    // Resolver hints.
    'resolver' => [
        'user_character_relation' => 'characters',
    ],

    // Check registrations.
    'checks' => [
        \Seat\SpyDetection\Checks\NegativeStandingWalletLinksCheck::class,
        \Seat\SpyDetection\Checks\AssetItemIndicatorsCheck::class,
        \Seat\SpyDetection\Checks\CorpAllianceHistoryRiskCheck::class,
        \Seat\SpyDetection\Checks\AltNetworkAnomaliesCheck::class,
    ],
];
