<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\User\Models\Alert;
use App\Domain\User\Repositories\AlertRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EloquentAlertRepository extends BaseRepository implements AlertRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Alert::class);
    }

    public function findByUser(int $userId): Collection
    {
        return Alert::where('user_id', $userId)->orderByDesc('created_at')->get();
    }

    public function findActiveByFrequency(string $frequency): Collection
    {
        return Alert::where('is_active', true)
            ->where('frequency', $frequency)
            ->with('user')
            ->get();
    }

    public function toggle(int $id): bool
    {
        $alert = $this->findByIdOrFail($id);
        $alert->update(['is_active' => !$alert->is_active]);

        return $alert->is_active;
    }
}
