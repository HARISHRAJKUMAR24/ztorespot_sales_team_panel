      <div
        class="sidebar border border-right col-md-3 col-lg-2 p-0 bg-body-tertiary">
        <div
          class="offcanvas-md offcanvas-end bg-body-tertiary"
          tabindex="-1"
          id="sidebarMenu"
          aria-labelledby="sidebarMenuLabel">
          <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="sidebarMenuLabel">
              Company name
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="offcanvas"
              data-bs-target="#sidebarMenu"
              aria-label="Close"></button>
          </div>
          <div
            class="offcanvas-body d-md-flex flex-column p-0 pt-lg-3 overflow-y-auto">
            <ul class="nav flex-column">
              <li class="nav-item">
                <a
                  class="nav-link d-flex align-items-center gap-2 active"
                  aria-current="page"
                  href="<?= BASE_URL ?>index.php">
                  <svg class="bi" aria-hidden="true">
                    <use xlink:href="#house-fill"></use>
                  </svg>
                  Dashboard
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="#">
                  <svg class="bi" aria-hidden="true">
                    <use xlink:href="#file-earmark"></use>
                  </svg>
                  Orders
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="#">
                  <svg class="bi" aria-hidden="true">
                    <use xlink:href="#cart"></use>
                  </svg>
                  Products
                </a>
              </li>


            </ul>

            <hr class="my-1" />
            <h6
              class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
              <span>Customers</span>

            </h6>
            <ul class="nav flex-column mb-auto">

              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="#">
                  <svg class="bi" aria-hidden="true">
                    <use xlink:href="#people"></use>
                  </svg>
                  Customers
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="#">
                  <svg class="bi" aria-hidden="true">
                    <use xlink:href="#register"></use>
                  </svg>
                  Registered Customers
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="#">
                  <svg class="bi" aria-hidden="true">
                    <use xlink:href="#whatsapp"></use>
                  </svg>
                  Whatsapp Customers
                </a>
              </li>

              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="#">
                  <svg class="bi" aria-hidden="true">
                    <use xlink:href="#share-fill"></use>
                  </svg>
                  Share Customer Data
                </a>
              </li>

              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2" href="#">
                  <svg class="bi" aria-hidden="true">
                    <use xlink:href="#organic-sellers"></use>
                  </svg>
                  Organic Sellers
                </a>
              </li>
            </ul>

            <hr class="my-1" />
            <h6
              class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
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
            <h6
              class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
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