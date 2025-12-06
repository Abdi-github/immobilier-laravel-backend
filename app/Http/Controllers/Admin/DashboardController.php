<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Agency\Models\Agency;
use App\Domain\Lead\Enums\LeadStatus;
use App\Domain\Lead\Models\Lead;
use App\Domain\Property\Enums\PropertyStatus;
use App\Domain\Property\Models\Property;
use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard/Index', [
            'stats' => fn () => $this->getStats(),
            'propertyStatusChart' => fn () => $this->getPropertyStatusDistribution(),
            'leadStatusChart' => fn () => $this->getLeadStatusDistribution(),
            'recentProperties' => fn () => $this->getRecentProperties(),
            'monthlyPropertyTrend' => fn () => $this->getMonthlyPropertyTrend(),
        ]);
    }

    private function getStats(): array
    {
        return [
            'total_properties' => Property::count(),
            'active_properties' => Property::where('status', PropertyStatus::PUBLISHED)->count(),
            'pending_properties' => Property::where('status', PropertyStatus::PENDING_APPROVAL)->count(),
            'total_agencies' => Agency::count(),
            'active_agencies' => Agency::active()->count(),
            'total_users' => User::count(),
            'active_users' => User::where('status', AccountStatus::ACTIVE)->count(),
            'total_leads' => Lead::count(),
            'new_leads' => Lead::where('status', LeadStatus::NEW)->count(),
            'properties_this_month' => Property::where('created_at', '>=', now()->startOfMonth())->count(),
            'leads_this_month' => Lead::where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    private function getPropertyStatusDistribution(): array
    {
        $counts = Property::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $labels = [];
        $data = [];

        foreach (PropertyStatus::cases() as $status) {
            $labels[] = str_replace('_', ' ', ucfirst(strtolower($status->value)));
            $data[] = $counts[$status->value] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getLeadStatusDistribution(): array
    {
        $counts = Lead::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $labels = [];
        $data = [];

        foreach (LeadStatus::cases() as $status) {
            $labels[] = str_replace('_', ' ', ucfirst(strtolower($status->value)));
            $data[] = $counts[$status->value] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getRecentProperties(): array
    {
        return Property::query()
            ->with(['category', 'canton', 'city', 'owner:id,first_name,last_name', 'translations'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Property $p) => [
                'id' => $p->id,
                'title' => $p->translations->firstWhere('language', 'en')?->title
                    ?? $p->translations->first()?->title
                    ?? $p->external_id,
                'status' => $p->status->value,
                'price' => $p->price,
                'transaction_type' => $p->transaction_type->value,
                'category' => $p->category?->getTranslation('name', 'en'),
                'canton' => $p->canton?->code,
                'city' => $p->city?->getTranslation('name', 'en'),
                'owner' => $p->owner ? $p->owner->first_name . ' ' . $p->owner->last_name : null,
                'created_at' => $p->created_at->toDateTimeString(),
            ])
            ->toArray();
    }

    private function getMonthlyPropertyTrend(): array
    {
        $months = collect(range(5, 0))->map(fn (int $i) => now()->subMonths($i)->startOfMonth());

        $labels = $months->map(fn ($m) => $m->format('M Y'))->toArray();

        $data = $months->map(function ($month) {
            return Property::query()
                ->where('created_at', '>=', $month)
                ->where('created_at', '<', $month->copy()->addMonth())
                ->count();
        })->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}
