<!DOCTYPE html>
<html>

<head>
    <title>PPPoE Monitoring</title>

    <link rel="stylesheet" href="assets/datatables/datatables.min.css">
    <link rel="stylesheet" href="assets/adminlte/adminlte.min.css">

    <script src="assets/jquery/jquery.min.js"></script>
    <script src="assets/datatables/datatables.min.js"></script>
</head>

<body>

    <div class="container-fluid mt-3">

        <div class="card">
            <div class="card-header">
                <h3>PPPoE Connection Monitoring</h3>
            </div>

            <div class="card-body">

                <table id="tblMonitoring" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>IP Address</th>
                            <th>Caller ID</th>
                            <th>Uptime</th>
                            <th>Router</th>
                            <th>Status</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                </table>

            </div>
        </div>

    </div>

    <script src="assets/js/index-old.js"></script>

</body>

</html>
