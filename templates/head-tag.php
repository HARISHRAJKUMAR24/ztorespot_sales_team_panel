<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="" />
  <meta
    name="author"
    content="Mark Otto, Jacob Thornton, and Bootstrap contributors" />
  <meta name="generator" content="Astro v5.13.2" />
  <title>Dashboard Template · Bootstrap v5.3</title>
  <link
    rel="canonical"
    href="https://getbootstrap.com/docs/5.3/examples/dashboard/" />
  <script src="<?= ASSETS_URL ?>js/color-modes.js"></script>
  <link href="<?= ASSETS_URL ?>dist/css/bootstrap.min.css" rel="stylesheet">
  <meta name="theme-color" content="#712cf9" />
  <link href="<?= BASE_URL ?>dashboard.css" rel="stylesheet" />
  <script>
    const BASE_URL = "<?= BASE_URL ?>";
  </script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <!-- In your head-tag.php file -->
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/scroll-fix.css">
  <style>
    .bd-placeholder-img {
      font-size: 1.125rem;
      text-anchor: middle;
      -webkit-user-select: none;
      -moz-user-select: none;
      user-select: none;
    }

    @media (min-width: 768px) {
      .bd-placeholder-img-lg {
        font-size: 3.5rem;
      }
    }

    .b-example-divider {
      width: 100%;
      height: 3rem;
      background-color: #0000001a;
      border: solid rgba(0, 0, 0, 0.15);
      border-width: 1px 0;
      box-shadow:
        inset 0 0.5em 1.5em #0000001a,
        inset 0 0.125em 0.5em #00000026;
    }

    .b-example-vr {
      flex-shrink: 0;
      width: 1.5rem;
      height: 100vh;
    }

    .bi {
      vertical-align: -0.125em;
      fill: currentColor;
    }

    .nav-scroller {
      position: relative;
      z-index: 2;
      height: 2.75rem;
      overflow-y: hidden;
    }

    .nav-scroller .nav {
      display: flex;
      flex-wrap: nowrap;
      padding-bottom: 1rem;
      margin-top: -1px;
      overflow-x: auto;
      text-align: center;
      white-space: nowrap;
      -webkit-overflow-scrolling: touch;
    }

    .btn-bd-primary {
      --bd-violet-bg: #712cf9;
      --bd-violet-rgb: 112.520718, 44.062154, 249.437846;
      --bs-btn-font-weight: 600;
      --bs-btn-color: var(--bs-white);
      --bs-btn-bg: var(--bd-violet-bg);
      --bs-btn-border-color: var(--bd-violet-bg);
      --bs-btn-hover-color: var(--bs-white);
      --bs-btn-hover-bg: #6528e0;
      --bs-btn-hover-border-color: #6528e0;
      --bs-btn-focus-shadow-rgb: var(--bd-violet-rgb);
      --bs-btn-active-color: var(--bs-btn-hover-color);
      --bs-btn-active-bg: #5a23c8;
      --bs-btn-active-border-color: #5a23c8;
    }

    .bd-mode-toggle {
      z-index: 1500;
    }

    .bd-mode-toggle .bi {
      width: 1em;
      height: 1em;
    }

    .bd-mode-toggle .dropdown-menu .active .bi {
      display: block !important;
    }

    /* Accordion customization */
    .accordion-item {
      background-color: transparent;
    }

    .accordion-button {
      box-shadow: none;
      color: inherit;
    }

    .accordion-button:not(.collapsed) {
      background-color: rgba(13, 110, 253, 0.05);
      color: #0d6efd;
    }

    .accordion-button:focus {
      box-shadow: none;
      border-color: transparent;
    }

    .accordion-button::after {
      background-size: 1rem;
      width: 1rem;
      height: 1rem;
      margin-left: auto;
    }

    /* Style for nested nav items */
    .nav-link.ps-4 {
      padding-left: 2.5rem !important;
      font-size: 0.9rem;
    }

    .nav-link.ps-4:hover {
      background-color: rgba(13, 110, 253, 0.05);
    }

    /* Active state for nested items */
    .nav-link.ps-4.active {
      color: #0d6efd;
      background-color: rgba(13, 110, 253, 0.1);
    }

    /* Smaller icons for nested items */
    .nav-link.ps-4 svg {
      width: 14px;
      height: 14px;
    }

    /* Hover effect for accordion button */
    .accordion-button:hover {
      background-color: rgba(13, 110, 253, 0.05);
    }
  </style>
</head>