const table = $('#tblMonitoring').DataTable({
    processing: true,
    serverSide: false,
    ajax: {
        url: 'ajax/get-active-sessions.php',
        type: 'GET'
    },
    columns: [
        {
            data: 'username'
        },
        {
            data: 'address'
        },
        {
            data: 'caller_id'
        },
        {
            data: 'uptime'
        },
        {
            data: 'router'
        },
        {
            data: 'status'
        },
        {
            data: 'action'
        }
    ]
});

setInterval(function () {
    table.ajax.reload(null, false);
}, 30000);

$(document).on('click', '.btn-disconnect', function () {
    const activeId = $(this).data('id');
    const routerId = $(this).data('router');

    if (!confirm('Disconnect user?')) {
        return;
    }

    $.post(
        'ajax/disconnect-session.php',
        {
            active_id: activeId,
            router_id: routerId
        },
        function (res) {
            alert(res.message);
            table.ajax.reload(null, false);
        },
        'json'
    );
});
