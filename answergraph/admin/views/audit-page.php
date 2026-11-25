<?php
/**
 * AnswerGraph Bulk Content Audit Dashboard
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap" style="max-width: 1100px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <div style="background: #ffffff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 18px 22px; margin: 16px 0;">
        <h1 style="margin: 0 0 4px; font-size: 20px; font-weight: 700; color: #0f172a;">Audit Semua Artikel &amp; Halaman (GEO, AEO, &amp; Regulasi ESDM)</h1>
        <p style="margin: 0; font-size: 13px; color: #475569;">
            Diurutkan berdasarkan artikel yang paling perlu perbaikan (temuan regulasi terbanyak dan skor GEO terendah).
        </p>
    </div>

    <div style="background: #ffffff; border: 1px solid #c3c4c7; border-radius: 6px; overflow: hidden;">
        <table class="widefat striped" style="border: none;">
            <thead>
                <tr>
                    <th style="width: 28%;">Judul &amp; Tipe</th>
                    <th style="width: 11%;">GEO Score</th>
                    <th style="width: 13%;">AEO Answer</th>
                    <th style="width: 12%;">Kesegaran</th>
                    <th style="width: 24%;">Temuan Regulasi / Anti-Slop</th>
                    <th style="width: 12%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)) : ?>
                    <tr>
                        <td colspan="6">Belum ada post atau halaman terpublikasi.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($rows as $row) :
                        $geo = (int) $row['geo_score'];
                        $geo_style = $geo >= 70
                            ? 'background:#dcfce7;color:#166534;'
                            : ($geo >= 40 ? 'background:#fef3c7;color:#854d0e;' : 'background:#fee2e2;color:#991b1b;');

                        $aeo_label = $row['aeo_status'] === 'green' ? 'Optimal' : ($row['aeo_status'] === 'orange' ? 'Perlu Tabel/List' : 'Belum Ada');
                        $aeo_style = $row['aeo_status'] === 'green'
                            ? 'background:#dcfce7;color:#166534;'
                            : ($row['aeo_status'] === 'orange' ? 'background:#fef3c7;color:#854d0e;' : 'background:#fee2e2;color:#991b1b;');

                        $fresh = isset($row['freshness']) ? $row['freshness'] : ['status' => 'unknown', 'days_old' => null];
                        $fresh_label = $fresh['status'] === 'fresh'
                            ? 'Segar (' . intval($fresh['days_old']) . ' hr)'
                            : ($fresh['status'] === 'stale' ? 'Usang (' . intval($fresh['days_old']) . ' hr)' : 'N/A');
                        $fresh_style = $fresh['status'] === 'fresh'
                            ? 'background:#dcfce7;color:#166534;'
                            : ($fresh['status'] === 'stale' ? 'background:#fef3c7;color:#854d0e;' : 'background:#f1f5f9;color:#475569;');

                        $issues = $row['lint']['issues'];
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($row['title']); ?></strong>
                                <div style="font-size: 11px; color: #475569; margin-top: 2px;">
                                    <code><?php echo esc_html($row['type']); ?></code> &middot; /<?php echo esc_html($row['slug']); ?>
                                </div>
                            </td>
                            <td>
                                <span style="display:inline-block;padding:3px 8px;border-radius:4px;font-weight:700;font-size:12px;<?php echo esc_attr($geo_style); ?>">
                                    <?php echo esc_html($geo); ?> / 100
                                </span>
                            </td>
                            <td>
                                <span style="display:inline-block;padding:3px 8px;border-radius:4px;font-weight:700;font-size:11px;<?php echo esc_attr($aeo_style); ?>">
                                    <?php echo esc_html($aeo_label); ?>
                                </span>
                            </td>
                            <td>
                                <span style="display:inline-block;padding:3px 8px;border-radius:4px;font-weight:700;font-size:11px;<?php echo esc_attr($fresh_style); ?>">
                                    <?php echo esc_html($fresh_label); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (empty($issues)) : ?>
                                    <span style="color: #166534; font-weight: 600; font-size: 12px;">Bersih (0 temuan)</span>
                                <?php else : ?>
                                    <ul style="margin: 0; padding-left: 16px; font-size: 12px; color: #991b1b;">
                                        <?php foreach ($issues as $iss) : ?>
                                            <li><?php echo esc_html($iss['message']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($row['edit_url']); ?>" class="button button-small" style="cursor: pointer;">Edit</a>
                                <a href="<?php echo esc_url($row['view_url']); ?>" target="_blank" rel="noopener" class="button button-small" style="cursor: pointer;">Lihat</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
