<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use RuntimeException;

class SeatDataLoader
{
    /**
     * @param int[] $characterIds
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function loadForCharacters(array $characterIds): array
    {
        return [
            'wallet_journals' => $this->loadWalletJournals($characterIds),
            'assets' => $this->loadAssets($characterIds),
            'corp_history' => $this->loadCorpHistory($characterIds),
            'characters' => $this->loadCharacters($characterIds),
        ];
    }

    /**
     * @param int[] $characterIds
     * @return array<int, array<string, mixed>>
     */
    private function loadWalletJournals(array $characterIds): array
    {
        $modelClass = $this->modelClass('wallet_journal');
        if (!$modelClass) {
            return [];
        }

        $days = (int) $this->cfg('scan.wallet_journal_days', 90);
        $limit = (int) $this->cfg('scan.wallet_journal_limit', 1000);
        $dateColumn = $this->column('wallet.date', 'date');
        $characterIdColumn = $this->column('wallet.character_id', 'character_id');

        $since = gmdate('Y-m-d H:i:s', time() - ($days * 86400));

        /** @var Model $modelClass */
        $rows = $modelClass::query()
            ->whereIn($characterIdColumn, $characterIds)
            ->where($dateColumn, '>=', $since)
            ->orderBy($dateColumn, 'desc')
            ->limit($limit)
            ->get();

        return $rows->map(function (Model $row) use ($characterIdColumn, $dateColumn): array {
            return [
                'character_id' => $row->getAttribute($characterIdColumn),
                'amount' => $row->getAttribute($this->column('wallet.amount', 'amount')),
                'date' => $row->getAttribute($dateColumn),
                'first_party_id' => $row->getAttribute($this->column('wallet.first_party_id', 'first_party_id')),
                'second_party_id' => $row->getAttribute($this->column('wallet.second_party_id', 'second_party_id')),
                'ref_type' => $row->getAttribute($this->column('wallet.ref_type', 'ref_type')),
                'reason' => $row->getAttribute($this->column('wallet.reason', 'reason')),
            ];
        })->all();
    }

    /**
     * @param int[] $characterIds
     * @return array<int, array<string, mixed>>
     */
    private function loadAssets(array $characterIds): array
    {
        $modelClass = $this->modelClass('asset');
        if (!$modelClass) {
            return [];
        }

        $limit = (int) $this->cfg('scan.asset_limit', 2000);
        $characterIdColumn = $this->column('asset.character_id', 'character_id');

        /** @var Model $modelClass */
        $rows = $modelClass::query()
            ->whereIn($characterIdColumn, $characterIds)
            ->limit($limit)
            ->get();

        return $rows->map(function (Model $row) use ($characterIdColumn): array {
            return [
                'character_id' => $row->getAttribute($characterIdColumn),
                'type_id' => $row->getAttribute($this->column('asset.type_id', 'type_id')),
                'group_id' => $row->getAttribute($this->column('asset.group_id', 'group_id')),
                'category_id' => $row->getAttribute($this->column('asset.category_id', 'category_id')),
                'quantity' => $row->getAttribute($this->column('asset.quantity', 'quantity')),
                'location_id' => $row->getAttribute($this->column('asset.location_id', 'location_id')),
                'location_type' => $row->getAttribute($this->column('asset.location_type', 'location_type')),
            ];
        })->all();
    }

    /**
     * @param int[] $characterIds
     * @return array<int, array<string, mixed>>
     */
    private function loadCorpHistory(array $characterIds): array
    {
        $modelClass = $this->modelClass('corporation_history');
        if (!$modelClass) {
            return [];
        }

        $limit = (int) $this->cfg('scan.corp_history_limit', 100);
        $characterIdColumn = $this->column('corp_history.character_id', 'character_id');
        $startDateColumn = $this->column('corp_history.start_date', 'start_date');

        /** @var Model $modelClass */
        $rows = $modelClass::query()
            ->whereIn($characterIdColumn, $characterIds)
            ->orderBy($startDateColumn, 'desc')
            ->limit($limit)
            ->get();

        return $rows->map(function (Model $row) use ($characterIdColumn, $startDateColumn): array {
            return [
                'character_id' => $row->getAttribute($characterIdColumn),
                'corporation_id' => $row->getAttribute($this->column('corp_history.corporation_id', 'corporation_id')),
                'alliance_id' => $row->getAttribute($this->column('corp_history.alliance_id', 'alliance_id')),
                'start_date' => $row->getAttribute($startDateColumn),
            ];
        })->all();
    }

    /**
     * @param int[] $characterIds
     * @return array<int, array<string, mixed>>
     */
    private function loadCharacters(array $characterIds): array
    {
        $modelClass = $this->modelClass('character');
        if (!$modelClass) {
            return [];
        }

        $characterIdColumn = $this->column('character_id', 'character_id');
        $nameColumn = $this->column('character_name', 'name');
        $birthdayColumn = $this->column('character_birthday', 'birthday');

        /** @var Model $modelClass */
        $rows = $modelClass::query()
            ->whereIn($characterIdColumn, $characterIds)
            ->get([$characterIdColumn, $nameColumn, $birthdayColumn]);

        return $rows->map(function (Model $row) use ($characterIdColumn, $nameColumn, $birthdayColumn): array {
            return [
                'character_id' => $row->getAttribute($characterIdColumn),
                'character_name' => $row->getAttribute($nameColumn),
                'birthday' => $row->getAttribute($birthdayColumn),
            ];
        })->all();
    }

    private function modelClass(string $key): ?string
    {
        $class = $this->cfg('models.' . $key);
        if (!is_string($class)) {
            return null;
        }
        if (!class_exists($class)) {
            return null;
        }

        return $class;
    }

    private function column(string $key, string $default): string
    {
        $column = $this->cfg('columns.' . $key);
        if (!is_string($column) || $column === '') {
            return $default;
        }

        return $column;
    }

    private function cfg(string $key, mixed $default = null): mixed
    {
        if (function_exists('config')) {
            return config('spy-detection.' . $key, $default);
        }

        return Arr::get([], $key, $default);
    }
}
