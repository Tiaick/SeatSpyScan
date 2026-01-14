<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Seat\SpyDetection\Engine\SpyCheckRunner;
use Seat\SpyDetection\Jobs\SpyDetectionScanJob;
use Seat\SpyDetection\Resolvers\SeatCharacterResolver;
use Seat\SpyDetection\Support\SpyContextBuilder;
use Throwable;

class SpyDetectionController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('spy-detection::spy-detection.index');
    }

    public function scan(
        Request $request,
        SpyContextBuilder $builder,
        SpyCheckRunner $runner,
        SeatCharacterResolver $resolver
    ): JsonResponse {
        $data = $request->validate([
            'character_name' => ['required', 'string', 'min:2'],
            'async' => ['sometimes', 'boolean'],
        ]);

        $requestedBy = (int) $request->user()->id;
        $characterName = trim((string) $data['character_name']);
        $useQueue = (bool) ($data['async'] ?? false);
        $forceQueue = (bool) (config('spy-detection.scan.force_queue', false));

        if ($forceQueue) {
            $useQueue = true;
        }

        try {
            $character = $resolver->findCharacterByName($characterName);
            if (!$character) {
                throw new \RuntimeException('Character not found.');
            }
            $characterId = (int) ($character['character_id'] ?? 0);
            if ($characterId <= 0) {
                throw new \RuntimeException('Character ID missing.');
            }
            $seatUserId = $resolver->findSeatUserIdForCharacter($characterId);
            if (!$seatUserId) {
                throw new \RuntimeException('No SeAT user linked to character.');
            }
            $characterIds = $resolver->listCharacterIdsForSeatUser($seatUserId);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ], 404);
        }

        if (!$useQueue && count($characterIds) > (int) config('spy-detection.scan.queue_if_chars_gt', 12)) {
            $useQueue = true;
        }

        if ($useQueue) {
            $token = (string) Str::uuid();
            $cacheKey = $this->cacheKey($token);
            $ttl = (int) config('spy-detection.scan.cache_ttl_minutes', 30);

            Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes($ttl));

            SpyDetectionScanJob::dispatch($token, $characterName, $requestedBy);

            return response()->json([
                'status' => 'pending',
                'token' => $token,
            ]);
        }

        $ctx = $builder->buildFromCharacterName($characterName, $requestedBy);
        $result = $runner->run($ctx);

        return response()->json([
            'status' => 'completed',
            'result' => $result->toArray(),
        ]);
    }

    public function status(string $token): JsonResponse
    {
        $cacheKey = $this->cacheKey($token);
        $payload = Cache::get($cacheKey);

        if (!$payload) {
            return response()->json([
                'status' => 'failed',
                'error' => 'Scan token expired or not found.',
            ], 404);
        }

        return response()->json($payload);
    }

    private function cacheKey(string $token): string
    {
        return 'spy_scan:' . $token;
    }
}
