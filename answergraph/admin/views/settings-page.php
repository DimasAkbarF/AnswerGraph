<?php
/**
 * AnswerGraph WordPress Admin Settings Page (v2.1.0)
 * Accessible, verified contrast, wired to wp_options, LLMs sync, IndexNow, Bot Telemetry & 1-Click Updater.
 */
if (!defined('ABSPATH')) exit;

$llms_url       = home_url('/llms.txt');
$full_url       = home_url('/llms-full.txt');
$sameas         = get_option('answergraph_sameas_urls', '');
$update_url     = get_option('answergraph_update_url', 'https://github.com/DimasAkbarF/AnswerGraph');
if ($update_url === '') {
    $update_url = 'https://github.com/DimasAkbarF/AnswerGraph';
}
$update_token   = get_option('answergraph_update_token', '');
$author_name    = get_option('answergraph_author_override', 'Tim Asesor & Auditor SMK2 Dantara Mandiri');
$allow_search   = get_option('answergraph_allow_search_bots', 'yes');
$allow_train    = get_option('answergraph_allow_training_bots', 'yes');
$block_scrape   = get_option('answergraph_block_scrapers', 'yes');
$ssr_lang_id    = get_option('answergraph_ssr_lang_id', 'yes');
$ssr_counter    = get_option('answergraph_ssr_counter_fix', 'yes');
$ssr_h1         = get_option('answergraph_ssr_h1_promote', 'yes');
$indexnow_on    = get_option('answergraph_indexnow_enabled', 'yes');
$preset_esdm    = get_option('answergraph_linter_preset_esdm', 'yes');
$custom_banned  = get_option('answergraph_custom_banned_terms', '');
$pillar_default = class_exists('AnswerGraph_Pillar_Links') ? AnswerGraph_Pillar_Links::DEFAULT_RULES : '';
$pillar_rules   = get_option('answergraph_pillar_rules', $pillar_default);

$bot_summary    = class_exists('AnswerGraph_Bot_Logger') ? (new AnswerGraph_Bot_Logger())->get_summary() : ['counts' => [], 'recent' => []];
$activity_logs  = class_exists('AnswerGraph_Bot_Logger') ? (new AnswerGraph_Bot_Logger())->get_events() : [];
$clear_logs_url = function_exists('wp_nonce_url')
    ? wp_nonce_url(admin_url('admin-post.php?action=answergraph_clear_logs'), 'answergraph_clear_logs_nonce')
    : '#';
$indexnow_key   = class_exists('AnswerGraph_IndexNow') ? (new AnswerGraph_IndexNow())->get_or_create_key() : '';
$ping_url       = function_exists('wp_nonce_url')
    ? wp_nonce_url(admin_url('admin-post.php?action=answergraph_ping_indexnow'), 'answergraph_ping_indexnow_nonce')
    : '#';

$llms_saved      = get_option('answergraph_llms_txt_content', '');
$llms_full_saved = get_option('answergraph_llms_full_txt_content', '');
if ($llms_saved === '' && file_exists(ABSPATH . 'llms.txt')) {
    $llms_saved = (string) @file_get_contents(ABSPATH . 'llms.txt');
}
if ($llms_full_saved === '' && file_exists(ABSPATH . 'llms-full.txt')) {
    $llms_full_saved = (string) @file_get_contents(ABSPATH . 'llms-full.txt');
}

$check_url = function_exists('wp_nonce_url')
    ? wp_nonce_url(admin_url('admin-post.php?action=answergraph_check_update'), 'answergraph_check_update_nonce')
    : '#';
$version_display = defined('ANSWERGRAPH_VERSION') ? ANSWERGRAPH_VERSION : '2.0.0';
?>

<div class="wrap" style="max-width: 980px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">

    <?php if (isset($_GET['llms_sync'])) : ?>
        <?php if ($_GET['llms_sync'] === 'ok') : ?>
            <div class="notice notice-success is-dismissible"><p><strong>AnswerGraph:</strong> File <code>llms.txt</code> dan <code>llms-full.txt</code> berhasil disimpan dan disinkronkan ke root situs.</p></div>
        <?php else : ?>
            <div class="notice notice-error is-dismissible"><p><strong>AnswerGraph:</strong> Pengaturan tersimpan di database, tetapi penulisan ke file statis root gagal (cek izin tulis direktori WordPress).</p></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_GET['indexnow_ping'])) : ?>
        <?php if ($_GET['indexnow_ping'] === 'ok') : ?>
            <div class="notice notice-success is-dismissible"><p><strong>IndexNow:</strong> URL utama dan <code>/llms.txt</code> berhasil dikirim ke Bing &amp; IndexNow API.</p></div>
        <?php else : ?>
            <div class="notice notice-error is-dismissible"><p><strong>IndexNow:</strong> Pengiriman ping IndexNow gagal. Pastikan server dapat mengakses <code>api.indexnow.org</code>.</p></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_GET['logs_cleared']) && $_GET['logs_cleared'] === 'ok') : ?>
        <div class="notice notice-success is-dismissible"><p><strong>AnswerGraph Log:</strong> Seluruh log kunjungan bot AI dan aktivitas sistem telah dibersihkan.</p></div>
    <?php endif; ?>

    <!-- Brand Header -->
    <div style="background: #ffffff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 20px 24px; margin: 16px 0 20px 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <span style="background: #06173a; color: #ffffff; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 3px; letter-spacing: 0.4px; text-transform: uppercase;">
                        DANTARA MANDIRI &bull; AEO/GEO
                    </span>
                    <span style="color: #475569; font-size: 12px; font-weight: 600;">v<?php echo esc_html($version_display); ?></span>
                </div>
                <h1 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; padding: 0;">
                    AnswerGraph - Modern AEO &amp; GEO Engine
                </h1>
                <p style="margin: 4px 0 0 0; color: #475569; font-size: 13px;">
                    Optimasi sitasi AI (ChatGPT, Perplexity, Claude, Gemini), Entity SEO, Linter Regulasi ESDM, LLMs.txt Manager, dan 1-Click Auto-Updater.
                </p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=answergraph-audit')); ?>" class="button button-secondary" style="font-size: 12px; font-weight: 600; cursor: pointer;">
                    Audit Semua Artikel
                </a>
                <a href="<?php echo esc_url($llms_url); ?>" target="_blank" rel="noopener" class="button button-secondary" style="font-size: 12px; font-weight: 600; cursor: pointer;">
                    Buka /llms.txt
                </a>
                <?php if ($update_url !== '') : ?>
                    <a href="<?php echo esc_url($check_url); ?>" class="button button-primary" style="font-size: 12px; font-weight: 600; cursor: pointer;">
                        Cek Update Plugin
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Settings Form Card -->
    <div style="background: #ffffff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 24px; margin-bottom: 24px;">
        <form method="post" action="options.php">
            <?php settings_fields('answergraph_options_group'); ?>

            <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">
                1. Auto-Update Plugin 1-Klik (WP Admin &rarr; Plugins)
            </h2>
            <p style="color: #475569; font-size: 13px; margin: 0 0 14px 0;">
                Isi URL repositori GitHub atau file JSON rilis (`update.json`) agar tombol <strong>Update Now</strong> muncul otomatis di daftar Plugin WordPress tanpa perlu upload ZIP manual.
            </p>
            <table class="form-table" style="margin-top: 0; margin-bottom: 20px;">
                <tr>
                    <th scope="row" style="width: 230px;"><label for="answergraph_update_url">URL Update / Repo GitHub</label></th>
                    <td>
                        <input id="answergraph_update_url" name="answergraph_update_url" type="url" value="<?php echo esc_attr($update_url); ?>" class="regular-text" style="width: 100%; max-width: 560px;" placeholder="https://github.com/org/repo atau https://domain.com/update.json" />
                        <p class="description" style="color: #475569;">Mendukung URL repo GitHub (`https://github.com/user/repo`), GitHub Releases API, atau URL `update.json` langsung.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="answergraph_update_token">GitHub Token (Opsional)</label></th>
                    <td>
                        <input id="answergraph_update_token" name="answergraph_update_token" type="password" value="<?php echo esc_attr($update_token); ?>" class="regular-text" style="width: 100%; max-width: 400px;" autocomplete="off" />
                        <p class="description" style="color: #475569;">Kosongkan jika repo publik atau self-hosted. Isi token read-only hanya jika memakai repo GitHub privat.</p>
                    </td>
                </tr>
            </table>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;" />

            <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">
                2. Entity Disambiguation (`sameAs` Schema) &amp; Author E-E-A-T
            </h2>
            <p style="color: #475569; font-size: 13px; margin: 0 0 14px 0;">
                Tautkan profil resmi perusahaan ke JSON-LD `Organization` dan ganti nama penulis internal (`drm` / `admindrm`) dengan nama tim asesor yang jelas.
            </p>
            <table class="form-table" style="margin-top: 0; margin-bottom: 20px;">
                <tr>
                    <th scope="row" style="width: 230px;"><label for="answergraph_sameas_urls">URL `sameAs` (1 per baris)</label></th>
                    <td>
                        <textarea id="answergraph_sameas_urls" name="answergraph_sameas_urls" rows="4" style="width: 100%; max-width: 560px; font-family: monospace; font-size: 12px;"><?php echo esc_textarea($sameas); ?></textarea>
                        <p class="description" style="color: #475569;">Masukkan hanya URL terverifikasi (misal halaman `/accreditation`, `/tim-ahli-kami`, LinkedIn resmi, atau Google Maps).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="answergraph_author_override">Pengganti Nama Author `drm`</label></th>
                    <td>
                        <input id="answergraph_author_override" name="answergraph_author_override" type="text" value="<?php echo esc_attr($author_name); ?>" class="regular-text" style="width: 100%; max-width: 420px;" />
                    </td>
                </tr>
            </table>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;" />

            <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">
                3. Pengaturan Bot AI (`robots.txt`) &amp; Perbaikan Teknis SSR
            </h2>
            <table class="form-table" style="margin-top: 0;">
                <tr>
                    <th scope="row" style="width: 230px;">Bot Pencari &amp; Training AI</th>
                    <td>
                        <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                            <input type="checkbox" name="answergraph_allow_search_bots" value="yes" <?php checked($allow_search, 'yes'); ?> />
                            <strong>Izinkan Bot Pencari AI</strong> (PerplexityBot, OAI-SearchBot, Applebot-Extended, Google-Extended)
                        </label>
                        <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                            <input type="checkbox" name="answergraph_allow_training_bots" value="yes" <?php checked($allow_train, 'yes'); ?> />
                            <strong>Izinkan GPTBot &amp; ClaudeBot</strong>
                        </label>
                        <label style="display: block; cursor: pointer;">
                            <input type="checkbox" name="answergraph_block_scrapers" value="yes" <?php checked($block_scrape, 'yes'); ?> />
                            <strong>Blokir CCBot &amp; Bytespider</strong>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Perbaikan Teknis SSR</th>
                    <td>
                        <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                            <input type="checkbox" name="answergraph_ssr_lang_id" value="yes" <?php checked($ssr_lang_id, 'yes'); ?> />
                            Paksa atribut bahasa <code>lang="id-ID"</code> &amp; Yoast <code>og:locale="id_ID"</code>
                        </label>
                        <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                            <input type="checkbox" name="answergraph_ssr_counter_fix" value="yes" <?php checked($ssr_counter, 'yes'); ?> />
                            Perbaiki widget Counter Elementor agar tidak merender <code>0%</code> di HTML awal
                        </label>
                        <label style="display: block; cursor: pointer;">
                            <input type="checkbox" name="answergraph_ssr_h1_promote" value="yes" <?php checked($ssr_h1, 'yes'); ?> />
                            Promosikan <code>&lt;h2&gt;</code> pertama ke <code>&lt;h1&gt;</code> pada halaman tanpa H1 (selain Beranda)
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">IndexNow (Bing &amp; ChatGPT)</th>
                    <td>
                        <label style="display: block; margin-bottom: 6px; cursor: pointer;">
                            <input type="checkbox" name="answergraph_indexnow_enabled" value="yes" <?php checked($indexnow_on, 'yes'); ?> />
                            <strong>Kirim ping IndexNow otomatis saat post/page diterbitkan</strong>
                        </label>
                        <?php if ($indexnow_key !== '') : ?>
                            <div style="font-size: 12px; color: #475569; margin-bottom: 6px;">
                                Key aktif: <code><?php echo esc_html($indexnow_key); ?></code>
                                <?php if ($indexnow_on === 'yes') : ?>
                                    &middot; <a href="<?php echo esc_url($ping_url); ?>" class="button button-small" style="cursor: pointer;">Ping IndexNow Sekarang</a>
                                <?php else : ?>
                                    <span style="color: #64748b;">(Aktifkan IndexNow &amp; simpan pengaturan untuk mengaktifkan file key dan ping manual)</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Linter &amp; Link Pilar</th>
                    <td>
                        <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                            <input type="checkbox" name="answergraph_linter_preset_esdm" value="yes" <?php checked($preset_esdm, 'yes'); ?> />
                            <strong>Aktifkan Preset Regulasi Dantara ESDM (SMK2 / Permen ESDM 10/2021)</strong>
                        </label>
                        <label for="answergraph_pillar_rules" style="display: block; font-weight: 600; font-size: 12px; margin-bottom: 4px;">Aturan Saran Link Pilar (`anchor => URL`, 1 per baris):</label>
                        <textarea id="answergraph_pillar_rules" name="answergraph_pillar_rules" rows="3" style="width: 100%; max-width: 560px; font-family: monospace; font-size: 12px; margin-bottom: 10px;"><?php echo esc_textarea($pillar_rules); ?></textarea>
                        <label for="answergraph_custom_banned_terms" style="display: block; font-weight: 600; font-size: 12px; margin-bottom: 4px;">Istilah Terlarang Kustom (1 per baris):</label>
                        <textarea id="answergraph_custom_banned_terms" name="answergraph_custom_banned_terms" rows="2" style="width: 100%; max-width: 560px; font-family: monospace; font-size: 12px;"><?php echo esc_textarea($custom_banned); ?></textarea>
                    </td>
                </tr>
            </table>

            <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <?php submit_button('Simpan Pengaturan AnswerGraph', 'primary'); ?>
            </div>
        </form>
    </div>

    <!-- AI Bot Traffic Telemetry & Activity Log -->
    <div style="background: #ffffff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 24px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 6px;">
            <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">
                4. Telemetri Bot AI &amp; Log Aktivitas Sistem
            </h2>
            <a href="<?php echo esc_url($clear_logs_url); ?>" class="button button-small" style="cursor: pointer;">
                Bersihkan Semua Log
            </a>
        </div>
        <p style="color: #475569; font-size: 13px; margin: 0 0 12px 0;">
            Mencatat 100 kunjungan terakhir bot AI (<code>GPTBot</code>, <code>OAI-SearchBot</code>, <code>PerplexityBot</code>, <code>ClaudeBot</code>, <code>Google-Extended</code>, <code>Applebot-Extended</code>) serta 50 log aktivitas IndexNow, LLMs sync, dan Updater.
        </p>
        <?php if (empty($bot_summary['counts'])) : ?>
            <p style="margin: 0 0 14px 0; font-size: 13px; color: #475569;">Belum ada kunjungan bot AI yang tercatat.</p>
        <?php else : ?>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
                <?php foreach ($bot_summary['counts'] as $bot_name => $cnt) : ?>
                    <span style="background: #f0f7ff; border: 1px solid #bae6fd; color: #0c4a6e; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700;">
                        <?php echo esc_html($bot_name); ?>: <?php echo intval($cnt); ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <table class="widefat striped" style="margin-bottom: 16px;">
                <thead><tr><th>Waktu (UTC)</th><th>Bot</th><th>Path</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach (array_slice(array_reverse($bot_summary['recent']), 0, 10) as $hit) : ?>
                        <tr>
                            <td><?php echo esc_html(isset($hit['time']) ? $hit['time'] : '-'); ?></td>
                            <td><strong><?php echo esc_html(isset($hit['bot']) ? $hit['bot'] : '-'); ?></strong></td>
                            <td><code><?php echo esc_html(isset($hit['path']) ? $hit['path'] : '/'); ?></code></td>
                            <td><?php echo isset($hit['status']) ? intval($hit['status']) : 0; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3 style="font-size: 13px; font-weight: 700; color: #0f172a; margin: 16px 0 8px 0;">
            Log Aktivitas Sistem (50 Terakhir)
        </h3>
        <?php if (empty($activity_logs)) : ?>
            <p style="margin: 0; font-size: 13px; color: #475569;">Belum ada aktivitas sistem yang tercatat (IndexNow, LLMs sync, atau cek update).</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead><tr><th style="width: 160px;">Waktu (UTC)</th><th style="width: 110px;">Modul</th><th style="width: 85px;">Status</th><th>Keterangan</th></tr></thead>
                <tbody>
                    <?php foreach (array_slice(array_reverse($activity_logs), 0, 20) as $ev) :
                        $lvl = isset($ev['level']) ? (string) $ev['level'] : 'info';
                        $lvl_style = $lvl === 'ok'
                            ? 'background:#dcfce7;color:#166534;'
                            : ($lvl === 'error' ? 'background:#fee2e2;color:#991b1b;' : 'background:#f1f5f9;color:#334155;');
                    ?>
                        <tr>
                            <td><?php echo esc_html(isset($ev['time']) ? $ev['time'] : '-'); ?></td>
                            <td><code><?php echo esc_html(isset($ev['source']) ? $ev['source'] : 'system'); ?></code></td>
                            <td>
                                <span style="display:inline-block;padding:2px 7px;border-radius:4px;font-weight:700;font-size:11px;text-transform:uppercase;<?php echo esc_attr($lvl_style); ?>">
                                    <?php echo esc_html($lvl); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(isset($ev['message']) ? $ev['message'] : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- LLMs.txt & LLMs-full.txt Direct Manager -->
    <div style="background: #ffffff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 24px;">
        <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">
            5. LLMs.txt &amp; LLMs-full.txt Manager (Sinkron ke Root Situs)
        </h2>
        <p style="color: #475569; font-size: 13px; margin: 0 0 16px 0;">
            Edit langsung isi <a href="<?php echo esc_url($llms_url); ?>" target="_blank" rel="noopener">/llms.txt</a> dan <a href="<?php echo esc_url($full_url); ?>" target="_blank" rel="noopener">/llms-full.txt</a> dari dashboard WordPress tanpa snippet WPCode tambahan.
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="answergraph_sync_llms" />
            <?php if (function_exists('wp_nonce_field')) wp_nonce_field('answergraph_sync_llms_nonce'); ?>

            <div style="margin-bottom: 16px;">
                <label for="answergraph_llms_txt_content" style="display: block; font-weight: 600; margin-bottom: 6px;">Isi `llms.txt` (Indeks Ringkas):</label>
                <textarea id="answergraph_llms_txt_content" name="answergraph_llms_txt_content" rows="10" style="width: 100%; font-family: monospace; font-size: 12px; line-height: 1.5;"><?php echo esc_textarea($llms_saved); ?></textarea>
            </div>

            <div style="margin-bottom: 16px;">
                <label for="answergraph_llms_full_txt_content" style="display: block; font-weight: 600; margin-bottom: 6px;">Isi `llms-full.txt` (Dokumentasi Lengkap):</label>
                <textarea id="answergraph_llms_full_txt_content" name="answergraph_llms_full_txt_content" rows="12" style="width: 100%; font-family: monospace; font-size: 12px; line-height: 1.5;"><?php echo esc_textarea($llms_full_saved); ?></textarea>
            </div>

            <button type="submit" class="button button-primary" style="cursor: pointer;">Simpan &amp; Sinkronkan llms.txt + llms-full.txt ke Root</button>
        </form>
    </div>
</div>
