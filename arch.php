<?php include "admin_header.php" ?>
<style>
    /* ================= GENERAL ================= */
    html,
    body {
        height: 100%;
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background-color: #0f172a;
        color: #e2e8f0;
    }

    /* ================= SEARCH FORM ================= */
    .search-card {
        background: #1e293b;
        border-radius: 12px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    }

    .search-card input.form-control,
    .search-card select.form-control {
        border-radius: 8px;
        padding: 8px;
        background-color: #0f172a;
        border: 1px solid #334155;
        color: #e2e8f0;
    }

    .search-card ::placeholder {
        color: #cbd5e1;
        opacity: 1;
    }

    .search-card select option {
        background-color: #1e293b;
        color: #e2e8f0;
    }

    .search-card input:focus,
    .search-card select:focus {
        border-color: #2563eb;
        background-color: rgba(37, 99, 235, 0.15);
        outline: none;
        box-shadow: 0 0 0 0.15rem rgba(37, 99, 235, 0.25);
    }

    .btn-outline-success {
        color: #22c55e;
        border-color: #22c55e;
    }

    .btn-outline-success:hover {
        background-color: #22c55e;
        color: #0f172a;
    }

    /* ================= TABLE ================= */
    .table {
        color: #f1f5f9 !important;
    }

    .table thead th {
        background: #475569 !important;
        color: #ffffff !important;
        text-align: center;
        border: none !important;
    }

    .table tbody tr {
        background: #1e293b;
        transition: 0.2s;
        text-align: center;
    }

    .table tbody tr:hover {
        background: #334155;
    }

    .table td {
        vertical-align: middle !important;
    }


    .scrollableBox {
        overflow-y: auto;
        max-height: 65vh;
    }

    .scrollableBox table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
    }

    .scrollableBox th,
    .scrollableBox td {
        padding: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .scrollableBox th {
        background-color: #334155;
        color: #f1f5f9;
        font-weight: 600;
    }

    .scrollableBox tr:nth-child(even) {
        background-color: rgba(255, 255, 255, 0.02);
    }

    .scrollableBox tr:hover {
        background-color: rgba(56, 189, 248, 0.1);
    }

    .scrollableBox button {
        font-size: 0.75rem;
        border-radius: 6px;
    }
</style>

<form action="export_excel.php" method="POST">
    <button style="
        padding:15px 30px;
        font-size:18px;
        background:#22c55e;
        color:white;
        border:none;
        border-radius:8px;"
        onclick="return confirm('Are you sure?');">
        Download Excel
    </button>
</form>
<hr>
<a href="addexcel.php">Add Data With excel</a>
<?php include "admin_footer.php" ?>
