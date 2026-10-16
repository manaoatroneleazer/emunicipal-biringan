<?php

namespace Emunicipal\Controllers\Resident;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Models\Request as RequestModel;

/**
 * ResidentDashboardController — resident dashboard page.
 * Design.md §8.1
 */
class ResidentDashboardController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::requireRole('resident');

        $userId = AuthMiddleware::userId();
        $stats  = [
            'total'           => 0,
            'pending'         => 0,
            'ready_for_claiming' => 0,
            'completed'       => 0,
            'under_verification'          => 0,
            'additional_info_required'    => 0,
            'approved'        => 0,
            'rejected'        => 0,
        ];
        $recentRequests = [];

        try {
            $requestModel   = new RequestModel();
            $allRequests    = $requestModel->findByUserId($userId);
            $stats['total'] = count($allRequests);
            foreach ($allRequests as $r) {
                if (isset($stats[$r['status']])) {
                    $stats[$r['status']]++;
                }
            }
            $recentRequests = array_slice($allRequests, 0, 5);
        } catch (\Throwable $e) {
            // Graceful degradation — show empty dashboard if DB unavailable
        }

        $this->renderWithLayout('resident/dashboard/index', [
            'title'          => 'Dashboard — eMunicipal',
            'activePage'     => 'dashboard',
            'stats'          => $stats,
            'recentRequests' => $recentRequests,
        ], 'layouts/resident');
    }
}
