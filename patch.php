<?php
/**
 * CMSNT Auto-Patcher
 * Integrated with admin panel UI
 */
define("IN_SITE", true);
require_once(__DIR__ . '/libs/db.php');
require_once(__DIR__ . '/libs/lang.php');
require_once(__DIR__ . '/libs/helper.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/libs/database/users.php');
$CMSNT = new DB();

// ==================== PATCH CONFIGURATION ====================
$patchTargets = [
    'libs/db.php' => [
        'name' => 'Database Lib',
        'desc' => 'Disabling core integrity checks (_sc, _vsc)',
        'patches' => [
            // Cleanup: Fix potential corruption from previous patcher (extra })
            ['pattern' => '/private function _sc\s*\(\)\{return true;\}\s*\}/s', 'replacement' => 'private function _sc(){return true;}'],
            ['pattern' => '/private function _vsc\s*\(\)\{return true;\}\s*\}/s', 'replacement' => 'private function _vsc(){return true;}'],

            // Standard Patch: Use recursive pattern (?1) to match balanced braces
            ['pattern' => '/private function _sc\s*\(\)\s*(\{((?>[^{}]+)|(?1))*\})/s', 'replacement' => 'private function _sc(){return true;}'],
            ['pattern' => '/private function _vsc\s*\(\)\s*(\{((?>[^{}]+)|(?1))*\})/s', 'replacement' => 'private function _vsc(){return true;}']
        ]
    ],
    'views/admin/sidebar.php' => [
        'name' => 'Admin Sidebar',
        'desc' => 'Disabling sidebar guard checks',
        'patches' => [
            ['pattern' => '/function __cmsnt_sidebar_guard\s*\(\)\s*(\{((?>[^{}]+)|(?1))*\})/s', 'replacement' => 'function __cmsnt_sidebar_guard(){return;}']
        ]
    ],
    'libs/helper.php' => [
        'name' => 'Helper Lib',
        'desc' => 'Fixing addon license verification',
        'patches' => [
            ['pattern' => '/function __cmsnt_helper_guard\s*\(\)\s*(\{((?>[^{}]+)|(?1))*\})/s', 'replacement' => 'function __cmsnt_helper_guard(){return;}'],
            ['pattern' => '/function checkAddonLicense\s*\(.*?\)\s*(\{((?>[^{}]+)|(?1))*\})/s', 'replacement' => 'function checkAddonLicense($licensekey, $project) { return ["msg" => "License Active", "status" => true]; }'],
            ['pattern' => '/function CMSNT_check_license\s*\(.*?\)\s*(\{((?>[^{}]+)|(?1))*\})/s', 'replacement' => 'function CMSNT_check_license($licensekey, $localkey = "") { return ["status" => "Active", "msg" => "License Active", "checkdate" => date("Ymd"), "localkey" => "valid", "md5hash" => "valid", "remotecheck" => true]; }']
        ]
    ],
    'libs/session.php' => [
        'name' => 'Session Lib',
        'desc' => 'Bypassing SecurityValidator',
        'patches' => [
            ['pattern' => '/public static function v\s*\(\)\s*(\{((?>[^{}]+)|(?1))*\})/s', 'replacement' => 'public static function v(){return true;}']
        ]
    ],
    'models/is_license.php' => [
        'name' => 'License Model',
        'desc' => 'Overriding license model logic',
        'patches' => [
            ['pattern' => '/function v3pX9sLic\s*\(.*?\)\s*(\{((?>[^{}]+)|(?1))*\})/s', 'replacement' => 'function v3pX9sLic($licensekey) { return ["msg" => "License Active", "status" => true]; }'],
            ['pattern' => '/function u2dK7mToken\s*\(.*?\)\s*(\{((?>[^{}]+)|(?1))*\})/s', 'replacement' => 'function u2dK7mToken($licensekey, $localkey = "") { return ["status" => "Active", "msg" => "License Active", "checkdate" => date("Ymd"), "localkey" => "valid", "md5hash" => "valid", "remotecheck" => true]; }']
        ]
    ],
    'views/admin/home.php' => [
        'name' => 'Admin Home',
        'desc' => 'Adding Patch Menu badge to dashboard',
        'patches' => [
            // Add Patch Menu badge after version badge if it doesn't exist
            [
                'pattern' => '/(<h5[^>]*>\s*<\?=\s*\$config\[\'project\'\];\s*\?>\s*<span[^>]*class="badge bg-primary"[^>]*>\s*<\?=\s*\$config\[\'version\'\];\s*\?>\s*<\/span>\s*)(?!.*Patch Menu)(\s*<\/h5>)/s',
                'replacement' => '$1' . "\n" . '                        <a href="<?= base_url(\'patch.php\'); ?>" class="text-decoration-none"><span class="badge bg-danger"' . "\n" . '                                style="font-size: 14px; padding: 5px 10px;">Patch Menu</span></a>$2'
            ]
        ]
    ]
];

// ==================== HELPER FUNCTIONS ====================

/**
 * Check if file has been patched by running regex and comparing
 */
function checkPatchStatus(string $filePath, array $patches): string
{
    if (!file_exists($filePath)) {
        return 'not_found';
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        return 'error';
    }

    foreach ($patches as $patch) {
        $result = preg_replace($patch['pattern'], $patch['replacement'], $content);
        if ($result !== null && $result !== $content) {
            return 'not_patched';
        }
    }

    return 'patched';
}

/**
 * Create backup file before patching
 */
function createBackup(string $filePath): bool
{
    $backupPath = $filePath . '.bak.' . date('Ymd_His');
    return copy($filePath, $backupPath);
}

/**
 * Apply patches to file
 */
function applyPatches(string $filePath, array $patches): array
{
    if (!file_exists($filePath)) {
        return ['status' => 'error', 'msg' => 'File does not exist: ' . $filePath];
    }

    if (!is_readable($filePath)) {
        return ['status' => 'error', 'msg' => 'Cannot read file: ' . $filePath];
    }

    if (!is_writable($filePath)) {
        return ['status' => 'error', 'msg' => 'No write permission for file: ' . $filePath];
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        return ['status' => 'error', 'msg' => 'Error reading file content'];
    }

    $originalContent = $content;
    $patchedCount = 0;

    foreach ($patches as $patch) {
        $newContent = preg_replace($patch['pattern'], $patch['replacement'], $content);
        if ($newContent !== null && $newContent !== $content) {
            $content = $newContent;
            $patchedCount++;
        }
    }

    if ($patchedCount === 0) {
        return ['status' => 'info', 'msg' => 'Already patched, no changes needed'];
    }

    // Create backup before writing
    if (!createBackup($filePath)) {
        return ['status' => 'error', 'msg' => 'Cannot create backup file'];
    }

    if (file_put_contents($filePath, $content) === false) {
        return ['status' => 'error', 'msg' => 'Error writing file'];
    }

    return ['status' => 'success', 'msg' => 'Patched successfully (' . $patchedCount . ' changes)'];
}

// ==================== PRE-CHECK STATUS ====================
$fileStatuses = [];
foreach ($patchTargets as $path => $info) {
    $fullPath = __DIR__ . '/' . $path;
    $fileStatuses[$path] = checkPatchStatus($fullPath, $info['patches']);
}

// ==================== HANDLE PATCH REQUEST ====================
$results = [];
$patchExecuted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patch'])) {
    $patchExecuted = true;

    foreach ($patchTargets as $path => $info) {
        $fullPath = __DIR__ . '/' . $path;
        $result = applyPatches($fullPath, $info['patches']);
        $result['name'] = $info['name'];
        $result['path'] = $path;
        $results[] = $result;
    }

    // Cap nhat lai trang thai sau khi patch
    $fileStatuses = [];
    foreach ($patchTargets as $path => $info) {
        $fullPath = __DIR__ . '/' . $path;
        $fileStatuses[$path] = checkPatchStatus($fullPath, $info['patches']);
    }
}

// ==================== SETUP ADMIN UI ====================
$body = [
    'title' => 'System Patcher | ' . $CMSNT->site('title'),
    'desc' => 'CMSNT Panel',
    'keyword' => 'cmsnt, patcher'
];
$body['header'] = '';
$body['footer'] = '';

require_once(__DIR__ . '/models/is_admin.php');
require_once(__DIR__ . '/views/admin/header.php');
require_once(__DIR__ . '/views/admin/sidebar.php');
require_once(__DIR__ . '/views/admin/nav.php');
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-wrench"></i> System Patcher</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= base_url_admin('home'); ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">System Patcher</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Developer Information -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card border-primary">
                    <div class="card-header bg-primary-transparent">
                        <div class="card-title text-primary">
                            <i class="fa-solid fa-code me-2"></i>Developer Information
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-2"><i class="fa-solid fa-user me-2 text-primary"></i>Mai Huy Bao</h6>
                                <p class="text-muted mb-2"><i class="fa-solid fa-briefcase me-2"></i>Backend Developer</p>
                                <p class="text-muted mb-0"><i class="fa-solid fa-info-circle me-2"></i>Specialized in PHP development, system security, and license bypass solutions</p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <div class="d-flex flex-column gap-2">
                                    <a href="https://facebook.com/maihuybao.developer" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-brands fa-facebook me-1"></i> Facebook
                                    </a>
                                    <a href="https://github.com/maihuybao" target="_blank" class="btn btn-sm btn-dark">
                                        <i class="fa-brands fa-github me-1"></i> GitHub
                                    </a>
                                    <a href="https://t.me/Mo_Ho_Bo" class="btn btn-sm btn-info">
                                        <i class="fa-brands fa-telegram me-1"></i> Telegram
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($patchExecuted && !empty($results)): ?>
            <!-- Patch Results -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">Patch Results</div>
                        </div>
                        <div class="card-body">
                            <?php foreach ($results as $res): ?>
                                <?php
                                $alertClass = 'alert-info';
                                $iconClass = 'fa-solid fa-circle-info';
                                if ($res['status'] === 'success') {
                                    $alertClass = 'alert-success';
                                    $iconClass = 'fa-solid fa-circle-check';
                                } elseif ($res['status'] === 'error') {
                                    $alertClass = 'alert-danger';
                                    $iconClass = 'fa-solid fa-circle-xmark';
                                }
                                ?>
                                <div class="alert <?= $alertClass; ?> d-flex align-items-center" role="alert">
                                    <i class="<?= $iconClass; ?> me-2 fs-16"></i>
                                    <div>
                                        <strong><?= htmlspecialchars($res['name']); ?></strong>
                                        <span class="text-muted ms-1">(<?= htmlspecialchars($res['path']); ?>)</span>
                                        <br>
                                        <small><?= htmlspecialchars($res['msg']); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title">File List</div>
                        <div>
                            <?php
                            $totalFiles = count($patchTargets);
                            $patchedFiles = count(array_filter($fileStatuses, fn($s) => $s === 'patched'));
                            ?>
                            <span class="badge bg-primary-transparent"><?= $patchedFiles; ?>/<?= $totalFiles; ?>
                                Patched</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            This tool performs bypass checks for license and system integrity.
                            Original files will be backed up before patching (*.bak).
                        </p>
                        <div class="table-responsive">
                            <table class="table table-bordered text-nowrap">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width: 5%;">#</th>
                                        <th scope="col" style="width: 15%;">Name</th>
                                        <th scope="col" style="width: 25%;">Path</th>
                                        <th scope="col" style="width: 35%;">Description</th>
                                        <th scope="col" style="width: 10%;">Patches</th>
                                        <th scope="col" style="width: 10%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 1;
                                    foreach ($patchTargets as $path => $info): ?>
                                        <?php
                                        $status = $fileStatuses[$path] ?? 'unknown';
                                        switch ($status) {
                                            case 'patched':
                                                $badgeClass = 'bg-success-transparent';
                                                $badgeText = 'Patched';
                                                break;
                                            case 'not_patched':
                                                $badgeClass = 'bg-warning-transparent';
                                                $badgeText = 'Not Patched';
                                                break;
                                            case 'not_found':
                                                $badgeClass = 'bg-danger-transparent';
                                                $badgeText = 'Not Found';
                                                break;
                                            default:
                                                $badgeClass = 'bg-secondary-transparent';
                                                $badgeText = 'Error';
                                                break;
                                        }
                                        ?>
                                        <tr>
                                            <td><?= $i++; ?></td>
                                            <td><strong><?= htmlspecialchars($info['name']); ?></strong></td>
                                            <td><code><?= htmlspecialchars($path); ?></code></td>
                                            <td><?= htmlspecialchars($info['desc']); ?></td>
                                            <td class="text-center"><?= count($info['patches']); ?></td>
                                            <td class="text-center"><span
                                                    class="badge <?= $badgeClass; ?>"><?= $badgeText; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <form method="post" style="display:inline;">
                                <button type="submit" name="patch" class="btn btn-primary">
                                    <i class="fa-solid fa-wrench me-1"></i> Patch All
                                </button>
                            </form>
                            <a href="<?= base_url_admin('home'); ?>" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                            </a>
                        </div>

                        <div class="mt-3">
                            <div class="alert alert-warning d-flex align-items-center" role="alert">
                                <i class="fa-solid fa-triangle-exclamation me-2 fs-16"></i>
                                <div>
                                    <strong>Note:</strong> Please delete <code>patch.php</code> after completing the
                                    patch to ensure system security.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/views/admin/footer.php');
?>