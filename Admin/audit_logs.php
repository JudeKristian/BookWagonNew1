<?php
session_start();
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "db_connect.php";

$adminId = intval($_SESSION['admin_id'] ?? 1);
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

// Handle Threat Resolution Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'resolve_threat') {
        $logId = intval($_POST['log_id'] ?? 0);
        $notes = trim($_POST['resolution_notes'] ?? 'Investigated and resolved by administrator');

        if ($logId > 0) {
            $stmt = $conn->prepare("UPDATE audit_logs SET is_resolved = 1, resolved_by = ?, resolved_at = NOW(), resolution_notes = ? WHERE id = ?");
            $stmt->bind_param("isi", $adminId, $notes, $logId);
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Security Threat #$logId marked as RESOLVED.";
            } else {
                $_SESSION['error_msg'] = "Failed to update threat status.";
            }
            $stmt->close();
        }
        header("Location: audit_logs.php?filter=" . urlencode($_GET['filter'] ?? 'all'));
        exit();
    } elseif ($_POST['action'] === 'unresolve_threat') {
        $logId = intval($_POST['log_id'] ?? 0);
        if ($logId > 0) {
            $stmt = $conn->prepare("UPDATE audit_logs SET is_resolved = 0, resolved_by = NULL, resolved_at = NULL, resolution_notes = NULL WHERE id = ?");
            $stmt->bind_param("i", $logId);
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Security Threat #$logId reopened as ACTIVE.";
            }
            $stmt->close();
        }
        header("Location: audit_logs.php?filter=" . urlencode($_GET['filter'] ?? 'all'));
        exit();
    }
}

// Session messages
$successMsg = $_SESSION['success_msg'] ?? '';
$errorMsg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// Get pending count for sidebar badge
$pendingSellers = 0;
$res = $conn->query("SELECT COUNT(*) as cnt FROM sellers WHERE status = 'pending'");
if ($res && $row = $res->fetch_assoc()) $pendingSellers = $row['cnt'];

// Active Unresolved Risk count
$unresolvedRiskCount = 0;
$riskRes = $conn->query("SELECT COUNT(*) as cnt FROM audit_logs WHERE (activity LIKE 'RISK%' OR action = 'RISK') AND (is_resolved = 0 OR is_resolved IS NULL)");
if ($riskRes && $row = $riskRes->fetch_assoc()) $unresolvedRiskCount = intval($row['cnt']);

// Resolved Risk count
$resolvedRiskCount = 0;
$resRiskRes = $conn->query("SELECT COUNT(*) as cnt FROM audit_logs WHERE (activity LIKE 'RISK%' OR action = 'RISK') AND is_resolved = 1");
if ($resRiskRes && $row = $resRiskRes->fetch_assoc()) $resolvedRiskCount = intval($row['cnt']);

// Total count
$totalCount = 0;
$totRes = $conn->query("SELECT COUNT(*) as cnt FROM audit_logs");
if ($totRes && $row = $totRes->fetch_assoc()) $totalCount = intval($row['cnt']);

$filter = $_GET['filter'] ?? 'all';

// Build filter condition
$whereClause = "";
if ($filter === 'risk') {
    $whereClause = "WHERE (a.activity LIKE 'RISK%' OR a.action = 'RISK') AND (a.is_resolved = 0 OR a.is_resolved IS NULL)";
} elseif ($filter === 'resolved') {
    $whereClause = "WHERE (a.activity LIKE 'RISK%' OR a.action = 'RISK') AND a.is_resolved = 1";
}

// Fetch logs with resolver admin info
$logs = [];
$sql = "SELECT a.id AS log_id, a.user_id, a.action, a.activity, a.details, a.ip_address, a.created_at, 
               a.is_resolved, a.resolved_by, a.resolved_at, a.resolution_notes,
               COALESCE(u.email, 'Admin/Direct') as user_email,
               adm.username as resolver_name
        FROM audit_logs a 
        LEFT JOIN users u ON a.user_id = u.id 
        LEFT JOIN admin adm ON a.resolved_by = adm.id
        $whereClause
        ORDER BY a.created_at DESC LIMIT 150";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - BookWagon Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .activity-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            background: var(--primary-light);
            color: var(--primary-dark);
        }
        .badge-risk {
            background: #fee2e2 !important;
            color: #b91c1c !important;
            border: 1px solid #f87171 !important;
            font-weight: 700 !important;
        }
        .badge-resolved {
            background: #dcfce7 !important;
            color: #15803d !important;
            border: 1px solid #86efac !important;
            font-weight: 600 !important;
        }
        .row-risk {
            background-color: #fff5f5 !important;
            border-left: 4px solid #ef4444 !important;
        }
        .row-resolved {
            background-color: #f0fdf4 !important;
            border-left: 4px solid #22c55e !important;
        }
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-muted);
            background: var(--card-bg);
            border: 1px solid var(--border);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .filter-tab:hover {
            color: var(--text-dark);
            border-color: #cbd5e1;
        }
        .filter-tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .filter-tab.tab-risk.active {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }
        .filter-tab.tab-risk:not(.active) {
            color: #dc2626;
            border-color: #fca5a5;
            background: #fff5f5;
        }
        .filter-tab.tab-resolved.active {
            background: #16a34a;
            color: white;
            border-color: #16a34a;
        }
        .filter-tab.tab-resolved:not(.active) {
            color: #15803d;
            border-color: #86efac;
            background: #f0fdf4;
        }
        .security-alert-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 5px solid #ef4444;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 3px rgba(239, 68, 68, 0.08);
        }
        .btn-solve-threat {
            background: #10b981;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        .btn-solve-threat:hover {
            background: #059669;
            color: white;
            transform: translateY(-1px);
        }
        .btn-reopen {
            background: none;
            border: 1px solid #cbd5e1;
            color: #64748b;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
        }
        .btn-reopen:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
    </style>
</head>
<body>

    <?php include "admin_sidebar.php"; ?>

    <main class="main-content">
        <div class="topbar">
            <div style="display: flex; align-items: center; gap: 12px;">
                <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
                <div class="topbar-title">
                    <h1>Audit & Security Logs</h1>
                    <p>Track user, system, and database intrusion activities</p>
                </div>
            </div>
            <div class="topbar-date">
                <i class="fa-regular fa-calendar"></i>
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>

        <div class="page-content">

            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($successMsg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($errorMsg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($unresolvedRiskCount > 0): ?>
                <div class="security-alert-box">
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: #991b1b; font-size: 15px;">
                            SECURITY WARNING: <?php echo $unresolvedRiskCount; ?> Active Database Threat(s) Require Review!
                        </div>
                        <div style="font-size: 13px; color: #b91c1c; margin-top: 3px;">
                            Autonomous triggers intercepted direct SQL modifications (such as pricing or book deletion) outside authenticated web sessions. Review the logs and click <strong>SOLVE</strong> once resolved.
                        </div>
                    </div>
                    <?php if ($filter !== 'risk'): ?>
                        <a href="audit_logs.php?filter=risk" style="background: #ef4444; color: white; padding: 8px 14px; border-radius: 8px; font-weight: 600; font-size: 12px; text-decoration: none; flex-shrink: 0;">
                            View <?php echo $unresolvedRiskCount; ?> Active Threat(s) <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="audit_logs.php" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-list"></i> All Activities (<?php echo $totalCount; ?>)
                </a>
                <a href="audit_logs.php?filter=risk" class="filter-tab tab-risk <?php echo $filter === 'risk' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-shield-virus"></i> Active Threats (<?php echo $unresolvedRiskCount; ?>)
                </a>
                <a href="audit_logs.php?filter=resolved" class="filter-tab tab-resolved <?php echo $filter === 'resolved' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-circle-check"></i> Solved / Dismissed (<?php echo $resolvedRiskCount; ?>)
                </a>
            </div>

            <div class="content-card">
                <div class="card-header-bar">
                    <h3>
                        <i class="fa-solid fa-shield-halved" style="color: var(--primary); margin-right: 8px;"></i>
                        <?php 
                            if ($filter === 'risk') echo 'Active Database Threats (Unresolved)';
                            elseif ($filter === 'resolved') echo 'Solved & Dismissed Security Threats';
                            else echo 'Recent Security Activities'; 
                        ?>
                    </h3>
                    <span style="font-size: 12px; color: var(--text-muted);"><?php echo count($logs); ?> entries shown</span>
                </div>

                <?php if(empty($logs)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <h3 style="font-size: 15px; font-weight: 600; color: var(--text-dark); margin-bottom: 4px;">No logs match this filter</h3>
                        <p><?php echo $filter === 'risk' ? 'All clear! No active or unresolved security threats.' : 'No security activities recorded.'; ?></p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Actor / Origin</th>
                                <th>Activity Status</th>
                                <th>Details / Forensic Evidence</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): 
                                $isRisk = ($log['action'] === 'RISK' || strpos($log['activity'], 'RISK') !== false);
                                $isResolved = !empty($log['is_resolved']) && $log['is_resolved'] == 1;
                            ?>
                            <tr class="<?php echo ($isRisk && !$isResolved) ? 'row-risk' : ($isResolved ? 'row-resolved' : ''); ?>">
                                <td style="white-space: nowrap;">
                                    <div style="font-size: 13px; font-weight: <?php echo ($isRisk && !$isResolved) ? '700' : '400'; ?>; color: <?php echo ($isRisk && !$isResolved) ? '#991b1b' : 'inherit'; ?>;">
                                        <?php echo date('M j, Y', strtotime($log['created_at'])); ?>
                                    </div>
                                    <div style="font-size: 11px; color: <?php echo ($isRisk && !$isResolved) ? '#b91c1c' : 'var(--text-light)'; ?>; margin-top: 2px;">
                                        <?php echo date('g:i:s A', strtotime($log['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isRisk && intval($log['user_id']) === 0): ?>
                                        <div style="font-weight: 700; font-size: 13px; color: #dc2626;">
                                            <i class="fa-solid fa-user-secret me-1"></i> Direct SQL Access
                                        </div>
                                        <div style="font-size: 11px; color: #b91c1c; margin-top: 2px;">
                                            <?php echo htmlspecialchars($log['ip_address'] ?? 'Direct DB / Console'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="font-weight: 600; font-size: 13px;">User #<?php echo htmlspecialchars($log['user_id']); ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"><?php echo htmlspecialchars($log['user_email']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isRisk && !$isResolved): ?>
                                        <span class="activity-type-badge badge-risk">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            ACTIVE RISK
                                        </span>
                                        <div style="font-size: 11px; font-weight: 700; color: #dc2626; margin-top: 4px;">
                                            <?php echo htmlspecialchars($log['activity']); ?>
                                        </div>
                                    <?php elseif ($isRisk && $isResolved): ?>
                                        <span class="activity-type-badge badge-resolved">
                                            <i class="fa-solid fa-check"></i>
                                            SOLVED
                                        </span>
                                        <div style="font-size: 11px; color: #15803d; margin-top: 4px; font-weight: 600;">
                                            <?php echo htmlspecialchars($log['activity']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="activity-type-badge">
                                            <i class="fa-solid fa-bolt" style="font-size: 10px;"></i>
                                            <?php echo htmlspecialchars($log['activity']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 13px; max-width: 420px; line-height: 1.4; color: <?php echo ($isRisk && !$isResolved) ? '#7f1d1d' : 'var(--text-muted)'; ?>;">
                                    <?php if ($isRisk && !$isResolved): ?>
                                        <div style="font-weight: 600; margin-bottom: 2px; color: #991b1b;">
                                            <i class="fa-solid fa-shield-virus me-1"></i> Tampering Detected
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div><?php echo htmlspecialchars($log['details']); ?></div>

                                    <?php if ($isResolved): ?>
                                        <div style="margin-top: 6px; padding: 6px 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; font-size: 12px; color: #166534;">
                                            <strong><i class="fa-solid fa-user-shield me-1"></i> Solved:</strong> <?php echo htmlspecialchars($log['resolution_notes'] ?: 'No notes entered'); ?>
                                            <span style="font-size: 11px; color: #15803d; display: block; margin-top: 2px;">
                                                By <?php echo htmlspecialchars($log['resolver_name'] ?: 'Admin #' . $log['resolved_by']); ?> on <?php echo date('M j, Y g:i A', strtotime($log['resolved_at'])); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <?php if ($isRisk && !$isResolved): ?>
                                        <button type="button" class="btn-solve-threat" onclick="openResolveModal(<?php echo $log['log_id']; ?>, '<?php echo htmlspecialchars(addslashes($log['activity'])); ?>', '<?php echo htmlspecialchars(addslashes($log['details'])); ?>')">
                                            <i class="fa-solid fa-circle-check"></i> SOLVE
                                        </button>
                                    <?php elseif ($isRisk && $isResolved): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Reopen this security threat as active?');">
                                            <input type="hidden" name="action" value="unresolve_threat">
                                            <input type="hidden" name="log_id" value="<?php echo $log['log_id']; ?>">
                                            <button type="submit" class="btn-reopen" title="Reopen Threat">
                                                <i class="fa-solid fa-rotate-left"></i> Reopen
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--text-light); font-size: 12px;">Routine</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Solve Threat Modal -->
    <div class="modal fade" id="resolveThreatModal" tabindex="-1" aria-labelledby="resolveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="resolveModalLabel">
                        <i class="fa-solid fa-circle-check text-success me-2"></i> Resolve Security Threat
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="audit_logs.php?filter=<?php echo urlencode($filter); ?>">
                    <input type="hidden" name="action" value="resolve_threat">
                    <input type="hidden" name="log_id" id="modalThreatId">
                    
                    <div class="modal-body pt-3">
                        <div class="alert alert-light border mb-3" style="font-size: 13px;">
                            <div class="fw-bold text-dark mb-1" id="modalThreatActivity"></div>
                            <div class="text-muted" id="modalThreatDetails"></div>
                        </div>

                        <label class="form-label fw-semibold text-dark" style="font-size: 13px;">Quick Resolution Reason:</label>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPresetNote('Original book pricing / data has been restored.')">
                                Data Restored
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPresetNote('Authorized developer maintenance test.')">
                                Maintenance Test
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPresetNote('Investigated and confirmed benign / dismissed.')">
                                Dismissed / Benign
                            </button>
                        </div>

                        <div class="mb-3">
                            <label for="resolutionNotes" class="form-label fw-semibold text-dark" style="font-size: 13px;">Resolution Notes / Remediation Taken:</label>
                            <textarea name="resolution_notes" id="resolutionNotes" class="form-control" rows="3" placeholder="Describe the action taken (e.g., reverted price to 200 pesos, verified DB change, etc.)..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold px-4">
                            <i class="fa-solid fa-check me-1"></i> Confirm & Mark Solved
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openResolveModal(id, activity, details) {
            document.getElementById('modalThreatId').value = id;
            document.getElementById('modalThreatActivity').innerText = activity;
            document.getElementById('modalThreatDetails').innerText = details;
            document.getElementById('resolutionNotes').value = 'Original book pricing / data has been restored.';
            
            const modal = new bootstrap.Modal(document.getElementById('resolveThreatModal'));
            modal.show();
        }

        function setPresetNote(note) {
            document.getElementById('resolutionNotes').value = note;
        }
    </script>
</body>
</html>
