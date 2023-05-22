<div class="postbox" style="margin-bottom: 0">
    <div class="inner">
        <table class="omsk-table">
            <thead>
            <tr>
                <th><?php _e( 'URL', 'openmost-site-kit' ); ?></th>
                <th><?php _e( 'Visits', 'openmost-site-kit' ); ?></th>
                <th><?php _e( 'Hits', 'openmost-site-kit' ); ?></th>
                <th><?php _e( 'Bounce Rate', 'openmost-site-kit' ); ?></th>
            </tr>
            </thead>
            <tbody id="osmk-pages-table-body">
            </tbody>
        </table>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', async function () {

        let response = await fetchMatomoApi({
            'method': 'Actions.getPageUrls',
            'period': 'range',
            'date': '<?php echo omsk_get_matomo_date(); ?>',
            'flat': 1,
            'filter_limit': 10,
        }).then(response => response.json());

        let tbody = document.getElementById('osmk-pages-table-body');
        let html = ``;
        Object.values(response.data).map((item) => {
            html += `<tr>
                            <td>${item.label}</td>
                            <td>${item.nb_visits}</td>
                            <td>${item.nb_hits}</td>
                            <td>${item.bounce_rate}</td>
                        </tr>`
        })

        tbody.innerHTML = html;
    });
</script>