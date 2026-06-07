<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // index — Full page load + SPA navigation
    // ──────────────────────────────────────────────────────────────

    public function index(Request $request): View|JsonResponse
    {
        $filters = $this->extractFilters($request);

        $initialLogs = $this->buildQuery($filters)
            ->with('causer:id,name,role')
            ->latest()
            ->paginate(25);

        // Users for select dropdowns in the filter bar
        $deliveryUsers   = User::deliveries()->active()->orderBy('name')->get(['id', 'name']);
        $callcenterUsers = User::callcenters()->active()->orderBy('name')->get(['id', 'name']);
        $adminUsers      = User::where('role', 'admin')->active()->orderBy('name')->get(['id', 'name']);

        $data = compact('initialLogs', 'deliveryUsers', 'callcenterUsers', 'adminUsers', 'filters');

        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html'        => view('admin.activity-log.partials.content', $data)->render(),
                'title'       => 'العمليات',
                'csrf_token'  => csrf_token(),
            ]);
        }

        return view('admin.activity-log.index', $data);
    }

    // ──────────────────────────────────────────────────────────────
    // data — AJAX polling endpoint
    // ──────────────────────────────────────────────────────────────

    public function data(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);

        $paginator = $this->buildQuery($filters)
            ->with('causer:id,name,role')
            ->latest()
            ->paginate(25);

        $rows = $paginator->getCollection()->map(function (ActivityLog $log) {
            return [
                'id'           => $log->id,
                'event'        => $log->event,
                'event_icon'   => $log->event_icon,
                'description'  => $log->description,
                'subject_type' => $log->subject_type,
                'subject_id'   => $log->subject_id,
                'subject_label'=> $log->subject_label,
                'causer_name'  => $log->causer?->name ?? '—',
                'causer_role'  => $log->causer_role,
                'causer_role_label' => $log->causer_role_label,
                'causer_role_badge' => $log->causer_role_badge,
                'properties'   => $log->properties,
                'created_at'   => str_replace(['AM','PM','am','pm'], ['ص','م','ص','م'], $log->created_at->format('Y-m-d h:i:s A')),
                'created_at_human' => $log->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'data'         => $rows,
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    private function extractFilters(Request $request): array
    {
        return [
            'log_id'       => $request->input('log_id') ? (int) $request->input('log_id') : null,
            'client_search'=> $request->input('client_search'),
            'order_number' => $request->input('order_number'),
            'delivery_id'  => $request->input('delivery_id') ? (int) $request->input('delivery_id') : null,
            'callcenter_id'=> $request->input('callcenter_id') ? (int) $request->input('callcenter_id') : null,
            'admin_id'     => $request->input('admin_id') ? (int) $request->input('admin_id') : null,
        ];
    }

    private function buildQuery(array $filters)
    {
        $query = ActivityLog::query()
            ->searchClient($filters['client_search'])
            ->forOrderNumber($filters['order_number']);

        // Log ID filter
        if ($filters['log_id']) {
            $query->where('id', $filters['log_id']);
        }

        // Delivery filter
        if ($filters['delivery_id']) {
            $query->where('causer_id', $filters['delivery_id'])
                  ->where(function ($q) {
                      $q->where('causer_role', 'delivery')
                        ->orWhere('causer_role', 'reserve_delivery');
                  });
        }

        // Callcenter filter
        if ($filters['callcenter_id']) {
            $query->where('causer_id', $filters['callcenter_id'])
                  ->where('causer_role', 'callcenter');
        }

        // Admin filter
        if ($filters['admin_id']) {
            $query->where('causer_id', $filters['admin_id'])
                  ->where('causer_role', 'admin');
        }

        return $query;
    }
}
