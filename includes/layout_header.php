<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle == "TaskMaster Pro - Chinh Phục Mục Tiêu, Bứt Phá Hiệu Suất";
?>
<!DOCTYPE html>
<html lang="vi" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="Quản lý công việc hiệu quả với TaskMaster Pro.">
    <meta name="keywords" content="task management, to-do list, productivity">
    <meta property="og:type" content="website">
    <meta property="og:title" content="TaskMaster Pro - Chinh Phục Mục Tiêu, Bứt Phá Hiệu Suất">
    <meta property="og:description" content="Quản lý công việc hiệu quả với TaskMaster Pro.">
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="TaskMaster Pro - Chinh Phục Mục Tiêu, Bứt Phá Hiệu Suất">
    <meta property="twitter:description" content="Quản lý công việc hiệu quả với TaskMaster Pro.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon" href="favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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

        body.modal-open {
            overflow: hidden;
        }

        body.sidebar-open-on-mobile {
            overflow: hidden;
        }

        .content-pusher {
            transition: margin-left 0.3s ease-in-out;
        }

        @media (min-width: 1024px) {
            body.sidebar-desktop-open .content-pusher {
                margin-left: 18rem;
            }

            body.admin-sidebar-desktop-open .admin-content-pusher {
                margin-left: 16rem;
            }
        }

        .task-item-actions button {
            transition: background-color 0.2s ease-in-out;
        }

        <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>.fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
            opacity: 0;
        }

        .fade-in-down {
            animation: fadeInDown 0.8s ease-out forwards;
            opacity: 0;
        }

        .delay-1 {
            animation-delay: 0.2s;
        }

        .delay-2 {
            animation-delay: 0.4s;
        }

        .delay-3 {
            animation-delay: 0.6s;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animated {
            opacity: 0;
        }

        .testimonial-slider-track {
            transition: transform 0.5s ease-in-out;
        }

        <?php endif; ?>
    </style>
    <?php if (isset($customCss) && !empty($customCss)): ?>
        <?php foreach ($customCss as $cssFile): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($cssFile); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($customJsInHead) && !empty($customJsInHead)): ?>
        <?php foreach ($customJsInHead as $jsFile): ?>
            <script defer src="<?php echo htmlspecialchars($jsFile); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<?php
if (basename($_SERVER['PHP_SELF']) == 'app.php') {
?>

    <body class="h-full bg-slate-100 text-slate-800 antialiased flex flex-col"
        x-data="taskApp(
        <?php echo htmlspecialchars(json_encode($tasksForCurrentPage), ENT_QUOTES, 'UTF-8'); ?>,
        <?php echo $userId; ?>,
        <?php echo $selectedListId ? $selectedListId : 'null'; ?>,
        '<?php echo htmlspecialchars($selectedListName, ENT_QUOTES, 'UTF-8'); ?>',
        <?php echo htmlspecialchars(json_encode($userTaskLists), ENT_QUOTES, 'UTF-8'); ?>
    )"
        :class="{
        'modal-open': globalModalOpenState,
        'sidebar-open-on-mobile': isSidebarOpen && !isDesktop,
        'sidebar-desktop-open': isSidebarOpen && isDesktop
    }"
        @open-modal.window="handleOpenModal($event.detail)"
        @close-modal.window="handleCloseModal($event.detail)"
        @toggle-sidebar.window="isSidebarOpen = !isSidebarOpen; console.log('App Root: Sidebar toggled by event. New state:', isSidebarOpen);"
        x-init="console.log('App Root: Alpine x-data taskApp scope INITIALIZED.')"
        x-cloak>
    <?php
} elseif (basename($_SERVER['PHP_SELF']) === 'index.php') { ?>

        <body class="h-full bg-slate-100 text-slate-800 antialiased flex flex-col"
            x-data x-cloak>
        <?php include 'includes/site_navigation.php';
    } elseif (basename($_SERVER['PHP_SELF']) === 'login.php' || basename($_SERVER['PHP_SELF']) === 'register.php') { ?>

            <body class="h-full bg-slate-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8"
                x-data="authForm()" x-cloak>
            <?php } ?>