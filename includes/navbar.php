
<?php
    require_once('../controller/main.php');

    $current_user = $db->query("select * from users WHERE user_id = '".$_SESSION['user_id']."'")->fetchArray();

?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <!-- Menu -->

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
            <div class="">
                <a href="index.html" class="brand">

                    <img src="./public/assets/img/icons/logo.svg" width="50px" />


                </a>

                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                    <i class="bx bx-chevron-left bx-sm align-middle"></i>
                </a>
            </div>

            <div class="menu-inner-shadow"></div>

            <ul class="menu-inner py-1">
                <!-- Links -->
                <?php
                    //get from user type
                    if($_SESSION['user_type'] == 'admin'){
                        //Finance user pages
                        ?>
                            <li class="menu-item active">
                                <a href="menu/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                                    <div data-i18n="Analytics">Dashboard</div>
                                </a>
                            </li>

                            <li class="menu-item ">
                                <a href="admin_transactions/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-layout"></i>
                                    <div data-i18n="Analytics">Transactions</div>
                                </a>
                            </li>
                            <li class="menu-item ">
                                <a href="users/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-layout"></i>
                                    <div data-i18n="Analytics">Users</div>
                                </a>
                            </li>
                            <li class="menu-item ">
                                <a href="admin_archive/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-layout"></i>
                                    <div data-i18n="Analytics">Archive</div>
                                </a>
                            </li>
                            <li class="menu-item ">
                                <a href="logs/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-layout"></i>
                                    <div data-i18n="Analytics">Logs</div>
                                </a>
                            </li>
                            <li class="menu-item ">
                                <a href="admin_profile/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-layout"></i>
                                    <div data-i18n="Analytics">Profile</div>
                                </a>
                            </li>
                            
                        <?php

                    }else if($_SESSION['user_type'] == 'finance'){
                        //Finance user pages
                        ?>
                            <li class="menu-item active">
                                <a href="finance_dashboard/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                                    <div data-i18n="Analytics">Dashboard</div>
                                </a>
                            </li>

                            <li class="menu-item ">
                                <a href="finance_transactions/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-layout"></i>
                                    <div data-i18n="Analytics">Transactions</div>
                                </a>
                            </li>
                            <li class="menu-item ">
                                <a href="finance_archive/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-layout"></i>
                                    <div data-i18n="Analytics">Archive</div>
                                </a>
                            </li>
                            <li class="menu-item ">
                                <a href="finance_profile/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-layout"></i>
                                    <div data-i18n="Analytics">Profile</div>
                                </a>
                            </li>
                        <?php
                    }
                    else{
                        // Get current user with role information
                        $current_user_role = $user->get_current_user();
                        $is_finance_team = $current_user_role && $user->is_finance_team($current_user_role['user_id']);

                        if ($is_finance_team) {
                            // Finance team pages
                            ?>
                            <li class="menu-item">
                                <a href="finance_dashboard/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-tachometer"></i>
                                    <div data-i18n="Analytics">Finance Dashboard</div>
                                </a>
                            </li>
                            <?php
                        }

                        // Regular user pages
                        ?>
                            <li class="menu-item active">
                                <a href="home/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                                    <div data-i18n="Analytics">Dashboard</div>
                                </a>
                            </li>

                            <li class="menu-item ">
                                <a href="user_transactions/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-layout"></i>
                                    <div data-i18n="Analytics">Transactions</div>
                                </a>
                            </li>
                            <li class="menu-item ">
                                <a href="user_profile/" class="menu-link">
                                    <i class="menu-icon tf-icons bx bx-layout"></i>
                                    <div data-i18n="Analytics">Profile</div>
                                </a>
                            </li>
                        <?php

                    }
                ?>
                

            </ul>
        </aside>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
            <!-- Navbar -->

            <nav class="layout-navbar container-fluid navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
                id="layout-navbar">
                <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                    <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                        <i class="bx bx-menu bx-sm"></i>
                    </a>
                </div>

                <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

                    <div class="navbar-nav align-items-center">
                        <div class="nav-item d-flex align-items-center nav-left">
                            <h4>UNDP FINANCE SYSTEM</h4>
                        </div>
                    </div>


                    <ul class="navbar-nav flex-row align-items-center ms-auto nav-right">
                        <!-- Notifications -->
                        <li class="nav-item lh-1 me-3">
                            <?php include('../includes/notifications.php'); ?>
                        </li>

                        <!-- Place this tag where you want the button to render. -->
                        <li class="nav-item lh-1 me-3">
                            <button id="theme-toggle" class="theme-switch">
                                <span class="sun-icon">
                                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="5" stroke="#6788f7" stroke-width="1.5" />
                                        <path d="M12 2V4" stroke="#6788f7" stroke-width="1.5" stroke-linecap="round" />
                                        <path d="M12 20V22" stroke="#6788f7" stroke-width="1.5"
                                            stroke-linecap="round" />
                                        <path d="M4 12L2 12" stroke="#6788f7" stroke-width="1.5"
                                            stroke-linecap="round" />
                                        <path d="M22 12L20 12" stroke="#6788f7" stroke-width="1.5"
                                            stroke-linecap="round" />
                                        <path opacity="0.5" d="M19.7778 4.22266L17.5558 6.25424" stroke="#6788f7"
                                            stroke-width="1.5" stroke-linecap="round" />
                                        <path opacity="0.5" d="M4.22217 4.22266L6.44418 6.25424" stroke="#6788f7"
                                            stroke-width="1.5" stroke-linecap="round" />
                                        <path opacity="0.5" d="M6.44434 17.5557L4.22211 19.7779" stroke="#6788f7"
                                            stroke-width="1.5" stroke-linecap="round" />
                                        <path opacity="0.5" d="M19.7778 19.7773L17.5558 17.5551" stroke="#6788f7"
                                            stroke-width="1.5" stroke-linecap="round" />
                                    </svg>
                                </span>
                                <span class="moon-icon">
                                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd"
                                            d="M22 12.0004C22 17.5232 17.5228 22.0004 12 22.0004C10.8358 22.0004 9.71801 21.8014 8.67887 21.4357C8.24138 20.3772 8 19.217 8 18.0004C8 15.7792 8.80467 13.7459 10.1384 12.1762C11.31 13.8818 13.2744 15.0004 15.5 15.0004C17.8615 15.0004 19.9289 13.741 21.0672 11.8572C21.3065 11.4612 22 11.5377 22 12.0004Z"
                                            fill="#6788f7" />
                                        <path
                                            d="M2 12C2 16.3586 4.78852 20.0659 8.67887 21.4353C8.24138 20.3768 8 19.2166 8 18C8 15.7788 8.80467 13.7455 10.1384 12.1758C9.42027 11.1303 9 9.86422 9 8.5C9 6.13845 10.2594 4.07105 12.1432 2.93276C12.5392 2.69347 12.4627 2 12 2C6.47715 2 2 6.47715 2 12Z"
                                            fill="#6788f7" />
                                    </svg>
                                </span>
                                <span class="switch-track">
                                    <span class="switch-thumb"></span>
                                </span>
                            </button>
                        </li>

                        <!-- User -->
                        <li class="nav-item navbar-dropdown dropdown-user dropdown">
                            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);"
                                data-bs-toggle="dropdown">
                                <div class="avatar avatar-online">
                                    <img src="./public/assets/img/avatars/1.png" alt
                                        class="w-px-40 h-auto rounded-circle" />
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown-bg">
                                <li>
                                    <a class="dropdown-item" href="#">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar avatar-online">
                                                    <img src="./public/assets/img/avatars/1.png" alt
                                                        class="w-px-40 h-auto rounded-circle" />
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <span class="fw-semibold d-block">John Doe</span>
                                                <small class="text-muted"><?php echo $_SESSION['user_type'] ?></small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <div class="dropdown-divider"></div>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#">
                                        <i class="bx bx-user me-2"></i>
                                        <span class="align-middle">My Profile</span>
                                    </a>
                                </li>

                                <li>
                                    <div class="dropdown-divider"></div>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="auth-login-basic.html">
                                        <i class="bx bx-power-off me-2"></i>
                                        <span class="align-middle">Log Out</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!--/ User -->
                    </ul>
                </div>
            </nav>

            <!-- / Navbar -->

            <!-- Content wrapper -->
            <div class="content-wrappe"></div>