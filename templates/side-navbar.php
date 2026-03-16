<div class="sidebar border border-right col-md-3 col-lg-2 p-0 bg-body-tertiary">
  <div class="offcanvas-md offcanvas-end bg-body-tertiary"
    tabindex="-1"
    id="sidebarMenu"
    aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
      <div class="offcanvas-title d-flex align-items-center gap-2" id="sidebarMenuLabel">
        <img src="<?= ASSETS_URL ?>images/logo-black.png" alt="Logo" height="35" class="d-inline-block">
        <span class="fw-semibold fs-6">Sales Team</span>
      </div>
      <button type="button"
        class="btn-close"
        data-bs-dismiss="offcanvas"
        data-bs-target="#sidebarMenu"
        aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-md-flex flex-column p-0 pt-lg-3 overflow-y-auto">
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 active"
            aria-current="page"
            href="<?= BASE_URL ?>index.php">
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
            Follw-ups
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2" href="#">
            <svg class="bi" aria-hidden="true">
              <use xlink:href="#shared-sellers"></use>
            </svg>
            Recived Sellers
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>work-station/reminders/renewal_sellers.php">
            <svg class="bi" aria-hidden="true">
              <use xlink:href="#renewal"></use>
            </svg>
            Renewel sellers
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

      <!-- SELLERS SECTION WITH DROPDOWN - ACCORDION STYLE -->
      <div class="accordion" id="sellersAccordion">
        <div class="accordion-item border-0 bg-transparent">
          <h2 class="accordion-header" id="sellersHeading">
            <button class="accordion-button collapsed bg-transparent px-3 py-2"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#sellersCollapse"
              aria-expanded="false"
              aria-controls="sellersCollapse">
              <svg class="bi me-2" aria-hidden="true" width="16" height="16">
                <use xlink:href="#people"></use>
              </svg>
              <span class="fw-semibold">Sellers</span>

            </button>
          </h2>
          <div id="sellersCollapse"
            class="accordion-collapse collapse"
            aria-labelledby="sellersHeading"
            data-bs-parent="#sellersAccordion">
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
                  <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>sellers/registered_sellers.php">
                    <svg class="bi" aria-hidden="true" width="14" height="14">
                      <use xlink:href="#register-customer"></use>
                    </svg>
                    Registered Sellers
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>sellers/whatsapp_customers.php">
                    <svg class="bi" aria-hidden="true" width="14" height="14">
                      <use xlink:href="#whatsapp"></use>
                    </svg>
                    Whatsapp Customers
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
                  <a class="nav-link d-flex align-items-center gap-2 ps-4" href="#">
                    <svg class="bi" aria-hidden="true" width="14" height="14">
                      <use xlink:href="#organic-sellers"></use>
                    </svg>
                    Organic Sellers
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link d-flex align-items-center gap-2 ps-4" href="<?= BASE_URL ?>sellers/upgrade_sellers.php">
                    <svg class="bi" aria-hidden="true" width="14" height="14">
                      <use xlink:href="#upgrade-sellers"></use>
                    </svg>
                    Upgrade Sellers
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Alternative: Using Bootstrap Dropdown (if you prefer dropdown instead of accordion) -->
      <!--
            <div class="dropdown mt-2">
                <button class="btn btn-link nav-link w-100 text-start d-flex align-items-center gap-2 dropdown-toggle" 
                        type="button" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false">
                    <svg class="bi" aria-hidden="true">
                        <use xlink:href="#people"></use>
                    </svg>
                    Sellers
                </button>
                <ul class="dropdown-menu w-100 border-0 shadow-sm">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>sellers/sales_person_sellers.php">Sellers</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>sellers/registered_sellers.php">Registered Sellers</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>sellers/whatsapp_customers.php">Whatsapp Customers</a></li>
                    <li><a class="dropdown-item" href="#">Share Seller Data</a></li>
                    <li><a class="dropdown-item" href="#">Organic Sellers</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>sellers/upgrade_sellers.php">Upgrade Sellers</a></li>
                </ul>
            </div>
            -->

      <hr class="my-1" />
      <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
        <span>About Team</span>
      </h6>
      <ul class="nav flex-column mb-auto">
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2" href="#">
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
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2" href="#">
            <svg class="bi" aria-hidden="true">
              <use xlink:href="#file-earmark-text"></use>
            </svg>
            Social engagement
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2" href="#">
            <svg class="bi" aria-hidden="true">
              <use xlink:href="#file-earmark-text"></use>
            </svg>
            Year-end sale
          </a>
        </li>
      </ul>

      <hr class="my-1" />
      <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
        <span>Bluk Upload</span>
      </h6>
      <ul class="nav flex-column mb-auto">
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>bulk-upload/whatsapp-bulk-upload.php">
            <svg class="bi" aria-hidden="true">
              <use xlink:href="#target"></use>
            </svg>
            Whatsapp Bulk Upload
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>bulk-upload/register-sellers-bulk-upload.php">
            <svg class="bi" aria-hidden="true">
              <use xlink:href="#register-customer"></use>
            </svg>
            Register Seller Bulk Upload
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>bulk-upload/upgrade-sellers-bulk-upload.php">
            <svg class="bi" aria-hidden="true">
              <use xlink:href="#upgrade-sellers"></use>
            </svg>
            Upgrade Sellers Bulk Upload
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>bulk-upload/sales_person_sellers_bulk_upload.php">
            <svg class="bi" aria-hidden="true">
              <use xlink:href="#upgrade-sellers"></use>
            </svg>
            Sales Sellers Bulk Upload
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
</div>