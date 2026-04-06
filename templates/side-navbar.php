<!-- Desktop Sidebar - hidden on mobile -->
<div class="sidebar border-end col-md-3 col-lg-2 p-0 bg-body-tertiary d-none d-md-flex flex-column">
    <div class="pt-lg-3 overflow-y-auto flex-grow-1">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2 active" aria-current="page" href="<?= BASE_URL ?>index.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#house-fill"></use>
                    </svg>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>work-station/workstation_add_seller.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#work-station"></use>
                    </svg>
                    Work Station
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>work-station/reminders/reminders.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#reminder"></use>
                    </svg>
                    Reminders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>work-station/follow-ups/sheets_followup_list.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#follow-up"></use>
                    </svg>
                    Follow-ups
                </a>
            </li>

        </ul>

        <hr class="my-1" />

        <div class="accordion" id="sellersAccordionDesktop">
            <div class="accordion-item border-0 bg-transparent">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-transparent px-3 py-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#sellersCollapseDesktop"
                        aria-expanded="false" aria-controls="sellersCollapseDesktop">
                        <svg class="bi me-2" aria-hidden="true" width="16" height="16">
                            <use xlink:href="#people"></use>
                        </svg>
                        <span class="fw-semibold">Sellers</span>
                    </button>
                </h2>
                <div id="sellersCollapseDesktop" class="accordion-collapse collapse">
                    <div class="accordion-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>sellers/sales_person_sellers.php">
                                    <svg class="bi" aria-hidden="true" width="14" height="14">
                                        <use xlink:href="#people"></use>
                                    </svg>
                                    Sellers
                                </a>
                            </li>
                            <!-- <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>work-station/share-sellers/share-seller.php">
                                    <svg class="bi" aria-hidden="true" width="14" height="14"><use xlink:href="#share-fill"></use></svg>
                                    Share Seller Data
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>work-station/received-sellers/received-sellers.php">
                                    <svg class="bi" aria-hidden="true"><use xlink:href="#shared-sellers"></use></svg>
                                    Received Sellers
                                </a>
                            </li> -->

                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>reports/call-reports.php">
                                    <svg class="bi" aria-hidden="true" width="14" height="14">
                                        <use xlink:href="#calls-reports"></use>
                                    </svg>
                                    Call Reports
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>reports/plan-reports.php">
                                    <svg class="bi" aria-hidden="true" width="14" height="14">
                                        <use xlink:href="#plan-reports"></use>
                                    </svg>
                                    Plan Reports
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>work-station/reminders/renewal_sellers.php">
                                    <svg class="bi" aria-hidden="true">
                                        <use xlink:href="#renewal"></use>
                                    </svg>
                                    Renewal sellers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>work-station/reminders/upgrade_sellers.php">
                                    <svg class="bi" aria-hidden="true">
                                        <use xlink:href="#upgrade_sellers"></use>
                                    </svg>
                                    Upgrade sellers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>work-station/reminders/refund_sellers.php">
                                    <svg class="bi" aria-hidden="true">
                                        <use xlink:href="#refund_sellers"></use>
                                    </svg>
                                    Refund sellers
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>sellers/organic-seller.php">
                                    <svg class="bi" aria-hidden="true" width="14" height="14">
                                        <use xlink:href="#organic-sellers"></use>
                                    </svg>
                                    Organic Sellers
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-1" />
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
            <span>About Team</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>target/target.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#target"></use>
                    </svg>
                    Targets
                </a>
            </li>

        </ul>

        <hr class="my-3" />
        <ul class="nav flex-column mb-auto">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>settings/settings.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#gear-wide-connected"></use>
                    </svg>
                    Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="#" id="logoutLink">
                    <svg class="bi" aria-hidden="true" width="16" height="16" fill="currentColor">
                        <use xlink:href="#door-closed"></use>
                    </svg>
                    Sign out
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Mobile Offcanvas Sidebar - separate from desktop, always in DOM -->
<div class="offcanvas offcanvas-start bg-body-tertiary" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
        <div class="d-flex align-items-center gap-2" id="sidebarMenuLabel">
            <img src="<?= ASSETS_URL ?>images/logo-black.png" alt="Logo" height="35" class="d-inline-block">
            <span class="fw-semibold fs-6">Sales Team</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0 pt-2">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2 active" aria-current="page" href="<?= BASE_URL ?>index.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#house-fill"></use>
                    </svg>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>work-station/workstation_add_seller.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#work-station"></use>
                    </svg>
                    Work Station
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>work-station/reminders/reminders.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#reminder"></use>
                    </svg>
                    Reminders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>work-station/follow-ups/sheets_followup_list.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#follow-up"></use>
                    </svg>
                    Follow-ups
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>work-station/reminders/renewal_sellers.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#renewal"></use>
                    </svg>
                    Renewal sellers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>work-station/reminders/upgrade_sellers.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#upgrade_sellers"></use>
                    </svg>
                    Upgrade sellers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>work-station/reminders/refund_sellers.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#refund_sellers"></use>
                    </svg>
                    Refund sellers
                </a>
            </li>
        </ul>

        <hr class="my-1" />

        <div class="accordion" id="sellersAccordionMobile">
            <div class="accordion-item border-0 bg-transparent">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-transparent px-3 py-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#sellersCollapseMobile"
                        aria-expanded="false" aria-controls="sellersCollapseMobile">
                        <svg class="bi me-2" aria-hidden="true" width="16" height="16">
                            <use xlink:href="#people"></use>
                        </svg>
                        <span class="fw-semibold">Sellers</span>
                    </button>
                </h2>
                <div id="sellersCollapseMobile" class="accordion-collapse collapse">
                    <div class="accordion-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>sellers/sales_person_sellers.php">
                                    <svg class="bi" aria-hidden="true" width="14" height="14">
                                        <use xlink:href="#people"></use>
                                    </svg>
                                    Sellers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>work-station/share-sellers/share-seller.php">
                                    <svg class="bi" aria-hidden="true" width="14" height="14">
                                        <use xlink:href="#share-fill"></use>
                                    </svg>
                                    Share Seller Data
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>work-station/received-sellers/received-sellers.php">
                                    <svg class="bi" aria-hidden="true">
                                        <use xlink:href="#shared-sellers"></use>
                                    </svg>
                                    Received Sellers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>sellers/organic-seller.php">
                                    <svg class="bi" aria-hidden="true" width="14" height="14">
                                        <use xlink:href="#organic-sellers"></use>
                                    </svg>
                                    Organic Sellers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>reports/call-reports.php">
                                    <svg class="bi" aria-hidden="true" width="14" height="14">
                                        <use xlink:href="#calls-reports"></use>
                                    </svg>
                                    Call Reports
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>reports/plan-reports.php">
                                    <svg class="bi" aria-hidden="true" width="14" height="14">
                                        <use xlink:href="#plan-reports"></use>
                                    </svg>
                                    Plan Reports
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-1" />
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
            <span>About Team</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>target/target.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#target"></use>
                    </svg>
                    Targets
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="#">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#graph-up"></use>
                    </svg>
                    Sales Team Reports
                </a>
            </li>
        </ul>

        <hr class="my-3" />
        <ul class="nav flex-column mb-auto">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>settings/settings.php">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#gear-wide-connected"></use>
                    </svg>
                    Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="#" id="logoutLinkMobile">
                    <svg class="bi" aria-hidden="true" width="16" height="16" fill="currentColor">
                        <use xlink:href="#door-closed"></use>
                    </svg>
                    Sign out
                </a>
            </li>
        </ul>
    </div>
</div>