<?php
/**
 * AnswerGraph WordPress Admin Settings Page (v2.0.0)
 * Accessible, verified contrast, wired to wp_options, LLMs sync, & 1-Click Updater.
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
            </table>

            <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <?php submit_button('Simpan Pengaturan AnswerGraph', 'primary'); ?>
            </div>
        </form>
    </div>

    <!-- LLMs.txt & LLMs-full.txt Direct Manager -->
    <div style="background: #ffffff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 24px;">
        <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">
            4. LLMs.txt &amp; LLMs-full.txt Manager (Sinkron ke Root Situs)
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
