<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = User::query()
            ->where('user_type', 'user')
            ->with(['defaultAddress.deliveryArea', 'latestOrder'])
            ->withCount('orders')
            ->withSum('orders as orders_total', 'total')
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->trim();

                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status') && $request->status !== 'all', fn (Builder $query) => $query->where('status', $request->status))
            ->when($request->filled('verified') && $request->verified !== 'all', function (Builder $query) use ($request) {
                $request->verified === 'verified'
                    ? $query->whereNotNull('phone_verified_at')
                    : $query->whereNull('phone_verified_at');
            })
            ->when($request->filled('date_range'), function (Builder $query) use ($request) {
                $dates = collect(explode(' to ', $request->date_range))
                    ->map(fn (string $date) => trim($date))
                    ->filter();

                if ($dates->count() === 2) {
                    $query->whereBetween('created_at', [
                        Carbon::parse($dates->first())->startOfDay(),
                        Carbon::parse($dates->last())->endOfDay(),
                    ]);
                } elseif ($dates->count() === 1) {
                    $query->whereDate('created_at', Carbon::parse($dates->first())->toDateString());
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statusCounts = User::query()
            ->where('user_type', 'user')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalCustomers = User::where('user_type', 'user')->count();
        $verifiedCustomers = User::where('user_type', 'user')->whereNotNull('phone_verified_at')->count();

        return view('admin.customers.index', compact(
            'customers',
            'statusCounts',
            'totalCustomers',
            'verifiedCustomers'
        ));
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->user_type === 'user', 404);

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', Rule::in([$customer->id])],
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($customer->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($customer->id),
            ],
        ]);

        unset($validated['customer_id']);

        $customer->fill($validated);

        if ($customer->isDirty('phone')) {
            $customer->phone_verified_at = null;
        }

        if ($customer->isDirty('email')) {
            $customer->email_verified_at = null;
        }

        $customer->save();

        flash_message('Customer information updated successfully!');

        return redirect()->route('customers.index', $request->query());
    }
}
