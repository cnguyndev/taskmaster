<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php?err=auth');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Panel - TaskMaster'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon" href="favicon.png" type="image/x-icon">
    <?php
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        [x-cloak] {
            display: none !important;
        }

        body.admin-modal-open {
            overflow: hidden;
        }

        body.admin-sidebar-open-on-mobile {
            overflow: hidden;
        }

        .admin-content-pusher {
            transition: margin-left 0.3s ease-in-out;
        }

        @media (min-width: 1024px) {
            .admin-sidebar-desktop-open .admin-content-pusher {
                margin-left: 16rem;
            }
        }
    </style>
    <?php if (isset($customCss) && !empty($customCss)): ?>
        <?php foreach ($customCss as $cssFile): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($cssFile); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<body class="h-full bg-slate-200 text-slate-800 antialiased flex flex-col"
    x-data="adminPageControls(
        <?php echo isset($usersForCurrentPage) ? htmlspecialchars(json_encode($usersForCurrentPage), ENT_QUOTES, 'UTF-8') : '[]'; ?>,
        <?php echo isset($userCurrentPage) ? $userCurrentPage : 1; ?>,
        <?php echo isset($userTotalPages) ? $userTotalPages : 0; ?>,
        <?php echo isset($userItemsPerPage) ? $userItemsPerPage : 15; ?>,
        <?php echo (int)$_SESSION['user_id']; ?>,
        <?php echo isset($sharedLinksForCurrentPage) ? htmlspecialchars(json_encode($sharedLinksForCurrentPage), ENT_QUOTES, 'UTF-8') : '[]'; ?>,
        <?php echo isset($linkCurrentPage) ? $linkCurrentPage : 1; ?>,
        <?php echo isset($linkTotalPages) ? $linkTotalPages : 0; ?>,
        <?php echo isset($linkItemsPerPage) ? $linkItemsPerPage : 15; ?>
      )"
    :class="{
        'admin-modal-open': showEditUserModal || showCreateUserModal, /* Thêm các modal khác của admin sau */
        'admin-sidebar-open-on-mobile': isSidebarOpen && !isDesktop,
        'admin-sidebar-desktop-open': isSidebarOpen && isDesktop
      }"
    @toggle-admin-sidebar.window="isSidebarOpen = !isSidebarOpen; console.log('Admin sidebar toggled by window event. New state:', isSidebarOpen);"
    x-init="
        console.log('Admin page BODY x-data adminPageControls scope INITIALIZED.');
        initAdminPage(); 
      "
    x-cloak>