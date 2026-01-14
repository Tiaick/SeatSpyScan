<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use RuntimeException;

class SeatCharacterResolver
{
    /**
     * @return array<string, mixed>|null
     */
    public function findCharacterByName(string $name): ?array
    {
        $modelClass = $this->modelClass('character');
        $characterNameColumn = $this->column('character_name');

        /** @var Model|null $character */
        $character = $modelClass::query()
            ->whereRaw('LOWER(' . $characterNameColumn . ') = ?', [mb_strtolower($name)])
            ->first();

        if (!$character) {
            return null;
        }

        return $character->toArray();
    }

    public function findSeatUserIdForCharacter(int $characterId): ?int
    {
        $modelClass = $this->modelClass('character');
        $characterIdColumn = $this->column('character_id');
        $characterUserIdColumn = $this->column('character_user_id', 'user_id');

        /** @var Model|null $character */
        $character = $modelClass::query()
            ->where($characterIdColumn, $characterId)
            ->first();

        if (!$character) {
            return null;
        }

        $userId = $character->getAttribute($characterUserIdColumn);
        if (is_numeric($userId)) {
            return (int) $userId;
        }

        $userModel = $this->modelClass('seat_user');
        $relation = $this->resolverConfig('user_character_relation', 'characters');
        if (method_exists($userModel, $relation)) {
            $user = $userModel::query()
                ->whereHas($relation, function ($query) use ($characterIdColumn, $characterId): void {
                    $query->where($characterIdColumn, $characterId);
                })
                ->first();

            return $user?->getAttribute('id');
        }

        return null;
    }

    /**
     * @return int[]
     */
    public function listCharacterIdsForSeatUser(int $userId): array
    {
        $characterIdColumn = $this->column('character_id');
        $characterUserIdColumn = $this->column('character_user_id', 'user_id');

        $userModel = $this->modelClass('seat_user');
        $relation = $this->resolverConfig('user_character_relation', 'characters');

        if (method_exists($userModel, $relation)) {
            $user = $userModel::query()->find($userId);
            if ($user) {
                $characters = $user->$relation()->pluck($characterIdColumn);
                return array_values(array_filter($characters->all(), 'is_numeric'));
            }
        }

        $characterModel = $this->modelClass('character');
        $ids = $characterModel::query()
            ->where($characterUserIdColumn, $userId)
            ->pluck($characterIdColumn);

        return array_values(array_filter($ids->all(), 'is_numeric'));
    }

    private function modelClass(string $key): string
    {
        $class = $this->cfg('models.' . $key);
        if (!is_string($class) || !class_exists($class)) {
            throw new RuntimeException('Missing model class for spy-detection: ' . $key);
        }

        return $class;
    }

    private function column(string $key, string $default = ''): string
    {
        $column = $this->cfg('columns.' . $key);
        if (!is_string($column) || $column === '') {
            return $default;
        }
        return $column;
    }

    private function resolverConfig(string $key, string $default): string
    {
        $value = $this->cfg('resolver.' . $key);
        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function cfg(string $key): mixed
    {
        if (function_exists('config')) {
            return config('spy-detection.' . $key);
        }

        return Arr::get([], $key);
    }
}
