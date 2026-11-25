<?php
/**
 * AnswerGraph Post Editor Meta Box View (v2.1.0)
 * Live AEO/GEO Analyzer, Configurable Linter, Pillar Linker, & Per-Post Schema Selector.
 */
if (!defined('ABSPATH')) exit;

$geo_score   = intval($geo_eval['geo_score']);
$aeo_status  = $aeo_eval['status'];
$lint_issues = isset($lint_eval['issues']) ? $lint_eval['issues'] : [];
$lint_count  = count($lint_issues);

if ($geo_score >= 70) {
    $geo_token = ['bg' => '#f0fdf4', 'border' => '#86efac', 'text' => '#166534', 'bar' => '#16a34a', 'label' => 'Sangat Siap'];
} elseif ($geo_score >= 40) {
    $geo_token = ['bg' => '#fefce8', 'border' => '#fde047', 'text' => '#854d0e', 'bar' => '#ca8a04', 'label' => 'Cukup Siap'];
} else {
    $geo_token = ['bg' => '#fef2f2', 'border' => '#fca5a5', 'text' => '#991b1b', 'bar' => '#dc2626', 'label' => 'Perlu Optimasi'];
}

if ($aeo_status === 'green') {
    $aeo_token = ['bg' => '#f0fdf4', 'border' => '#86efac', 'text' => '#166534', 'pill' => '#15803d', 'title' => 'Optimal (40-60 Kata)', 'desc' => 'Definisi padat terdeteksi di bawah subjudul. Memenuhi standar Featured Snippet Google & AI Overview.'];
} elseif ($aeo_status === 'orange') {
    $aeo_token = ['bg' => '#fefce8', 'border' => '#fde047', 'text' => '#854d0e', 'pill' => '#ca8a04', 'title' => 'Cukup (Perlu Tabel/List)', 'desc' => 'Teks definisi ditemukan, namun perlu dilengkapi tabel data atau daftar bernomor (<ol>) agar mudah diekstraksi LLM.'];
} else {
    $aeo_token = ['bg' => '#fef2f2', 'border' => '#fca5a5', 'text' => '#991b1b', 'pill' => '#dc2626', 'title' => 'Belum Ada Direct Answer', 'desc' => 'Belum ditemukan paragraf rangkuman 40-60 kata tepat di bawah heading pertanyaan (H2/H3).'];
}

$current_slug = isset($slug) ? (string) $slug : '';
$current_schema = isset($schema_type) ? (string) $schema_type : 'default';
$preset_esdm_flag = !isset($preset_esdm) || !empty($preset_esdm) ? '1' : '0';
$custom_banned_list = isset($custom_banned) && is_array($custom_banned) ? array_values($custom_banned) : [];
$custom_banned_json = function_exists('wp_json_encode') ? wp_json_encode($custom_banned_list) : json_encode($custom_banned_list);
$pillar_list = isset($pillar_opps) && is_array($pillar_opps) ? $pillar_opps : [];
?>

<div class="answergraph-metabox-root" data-ag-elementor="<?php echo !empty($has_elementor) ? '1' : '0'; ?>" data-ag-slug="<?php echo esc_attr($current_slug); ?>" data-ag-preset-esdm="<?php echo esc_attr($preset_esdm_flag); ?>" data-ag-custom-banned="<?php echo esc_attr((string) $custom_banned_json); ?>" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; font-size: 13px; line-height: 1.5; background: #ffffff;">

    <style>
        .answergraph-metabox-root * { box-sizing: border-box; }
        .answergraph-tab-btn {
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .answergraph-tab-btn:hover { color: #0f172a; border-bottom-color: #cbd5e1; }
        .answergraph-tab-btn.active { color: #06173a; border-bottom-color: #06173a; background: #f8fafc; }
        .answergraph-tab-btn:focus-visible { outline: 3px solid #0369a1; outline-offset: 2px; }
        .answergraph-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .answergraph-metric-card { border-radius: 6px; padding: 14px 16px; border: 1px solid; }
        .answergraph-checklist-row {
            display: grid;
            grid-template-columns: 24px 1fr auto;
            gap: 12px;
            align-items: start;
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .answergraph-checklist-row:last-child { border-bottom: none; }
        .answergraph-checklist-row:hover { background: #f8fafc; }
    </style>

    <!-- Top Navigation Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 2px; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
        <div style="display: flex; gap: 2px;" role="tablist">
            <button type="button" class="answergraph-tab-btn active" onclick="answergraphSwitchTab(this, 'answergraph-tab-overview')">
                Skor &amp; Analisis AI
            </button>
            <button type="button" class="answergraph-tab-btn" onclick="answergraphSwitchTab(this, 'answergraph-tab-checklist')">
                Checklist &amp; Regulasi ESDM
                <span data-ag="lint-badge" class="answergraph-pill" style="<?php echo $lint_count > 0 ? 'background:#fee2e2;color:#991b1b;' : 'background:#dcfce7;color:#166534;'; ?>">
                    <?php echo esc_html($lint_count); ?>
                </span>
            </button>
            <button type="button" class="answergraph-tab-btn" onclick="answergraphSwitchTab(this, 'answergraph-tab-tools')">
                Schema &amp; Markdown AI
            </button>
        </div>
        <div style="font-size: 11px; color: #475569; display: flex; align-items: center; gap: 8px;">
            <button type="button" class="button button-small" onclick="answergraphRunLiveAnalysis(true, false)" style="cursor: pointer;">Refresh Skor</button>
            <span>Dantara Mandiri &bull; AEO/GEO v<?php echo defined('ANSWERGRAPH_VERSION') ? esc_html(ANSWERGRAPH_VERSION) : '2.0.0'; ?></span>
        </div>
    </div>

    <!-- TAB 1: OVERVIEW & SCORES -->
    <div id="answergraph-tab-overview" class="answergraph-tab-content">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
            <div data-ag="geo-card" class="answergraph-metric-card" style="background: <?php echo esc_attr($geo_token['bg']); ?>; border-color: <?php echo esc_attr($geo_token['border']); ?>;">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">GEO Citation Score</span>
                        <div data-ag="geo-score-text" style="font-size: 18px; font-weight: 800; color: <?php echo esc_attr($geo_token['text']); ?>; margin-top: 2px;">
                            <span data-ag="geo-score-num"><?php echo esc_html($geo_score); ?></span> <span style="font-size: 13px; font-weight: 600; color: #475569;">/ 100</span>
                        </div>
                    </div>
                    <span data-ag="geo-pill" class="answergraph-pill" style="background: #ffffff; color: <?php echo esc_attr($geo_token['text']); ?>; border: 1px solid <?php echo esc_attr($geo_token['border']); ?>;">
                        <?php echo esc_html($geo_token['label']); ?>
                    </span>
                </div>
                <div data-ag="geo-progressbar" role="progressbar" aria-valuenow="<?php echo esc_attr($geo_score); ?>" aria-valuemin="0" aria-valuemax="100" style="background: #e2e8f0; border-radius: 4px; height: 6px; overflow: hidden; margin: 8px 0 10px 0;">
                    <div data-ag="geo-bar" style="background: <?php echo esc_attr($geo_token['bar']); ?>; height: 100%; width: <?php echo esc_attr($geo_score); ?>%; border-radius: 4px; transition: width 0.3s ease;"></div>
                </div>
                <div style="font-size: 12px; color: #334155; line-height: 1.4;">
                    Probabilitas disitir ChatGPT, Perplexity &amp; Gemini berdasarkan paper empiris Princeton University (KDD 2024).
                </div>
            </div>

            <div data-ag="aeo-card" class="answergraph-metric-card" style="background: <?php echo esc_attr($aeo_token['bg']); ?>; border-color: <?php echo esc_attr($aeo_token['border']); ?>;">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">AEO Direct Answer Box</span>
                        <div data-ag="aeo-title" style="font-size: 15px; font-weight: 700; color: <?php echo esc_attr($aeo_token['text']); ?>; margin-top: 2px;">
                            <?php echo esc_html($aeo_token['title']); ?>
                        </div>
                    </div>
                    <span data-ag="aeo-dot" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: <?php echo esc_attr($aeo_token['pill']); ?>;"></span>
                </div>
                <div data-ag="aeo-desc" style="font-size: 12px; color: #334155; line-height: 1.4; margin-top: 10px;">
                    <?php echo esc_html($aeo_token['desc']); ?>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; margin-bottom: 16px;">
            <div>
                <span style="font-size: 11px; color: #475569; font-weight: 600; text-transform: uppercase;">Metrik Data Kuantitatif</span>
                <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                    <span data-ag="metric-stats"><?php echo intval($geo_eval['stats_count']); ?></span> Angka/Statistik
                </div>
            </div>
            <div>
                <span style="font-size: 11px; color: #475569; font-weight: 600; text-transform: uppercase;">Kutipan Otoritatif</span>
                <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                    <span data-ag="metric-quotes"><?php echo intval($geo_eval['quotes_count']); ?></span> Rujukan Regulasi
                </div>
            </div>
            <div>
                <span style="font-size: 11px; color: #475569; font-weight: 600; text-transform: uppercase;">Tautan Domain Otoritas</span>
                <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                    <span data-ag="metric-ext"><?php echo intval($geo_eval['external_citations_count']); ?></span> Sumber Eksternal
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: CHECKLIST & DANTARA REGULATORY LINTER -->
    <div id="answergraph-tab-checklist" class="answergraph-tab-content" style="display: none;">
        <div style="border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; margin-bottom: 16px;">
            <div style="background: #f8fafc; padding: 10px 14px; font-weight: 700; font-size: 12px; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;">
                Checklist Analisis Mesin Penjawab AI
            </div>

            <div class="answergraph-checklist-row">
                <div>&bull;</div>
                <div>
                    <div style="font-weight: 600; color: #0f172a;">Data Statistik &amp; Angka Spesifik</div>
                    <div style="font-size: 12px; color: #475569;">Terdeteksi <strong><span data-ag="chk-stats-num"><?php echo intval($geo_eval['stats_count']); ?></span> metrik kuantitatif</strong>.</div>
                </div>
                <div>
                    <span data-ag="chk-stats-pill" class="answergraph-pill" style="<?php echo $geo_eval['stats_count'] >= 2 ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#991b1b;'; ?>">
                        <?php echo $geo_eval['stats_count'] >= 2 ? 'Lolos' : 'Perlu Tambahan'; ?>
                    </span>
                </div>
            </div>

            <div class="answergraph-checklist-row">
                <div>&bull;</div>
                <div>
                    <div style="font-weight: 600; color: #0f172a;">Kutipan Ahli / Regulasi Resmi</div>
                    <div style="font-size: 12px; color: #475569;">Terdeteksi <strong><span data-ag="chk-quotes-num"><?php echo intval($geo_eval['quotes_count']); ?></span> kutipan otoritatif</strong> (<code>&lt;blockquote&gt;</code> / kutipan atribusi).</div>
                </div>
                <div>
                    <span data-ag="chk-quotes-pill" class="answergraph-pill" style="<?php echo $geo_eval['quotes_count'] >= 1 ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#991b1b;'; ?>">
                        <?php echo $geo_eval['quotes_count'] >= 1 ? 'Lolos' : 'Belum Ada'; ?>
                    </span>
                </div>
            </div>

            <div class="answergraph-checklist-row">
                <div>&bull;</div>
                <div>
                    <div style="font-weight: 600; color: #0f172a;">Direct Answer Box (Inverted Pyramid)</div>
                    <div style="font-size: 12px; color: #475569;">Paragraf 40-60 kata langsung di bawah subjudul pertanyaan (H2/H3).</div>
                </div>
                <div>
                    <span data-ag="chk-direct-pill" class="answergraph-pill" style="<?php echo $aeo_eval['has_direct_answer_box'] ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#991b1b;'; ?>">
                        <?php echo $aeo_eval['has_direct_answer_box'] ? 'Optimal' : 'Perlu Diisi'; ?>
                    </span>
                </div>
            </div>

            <div class="answergraph-checklist-row">
                <div>&bull;</div>
                <div>
                    <div style="font-weight: 600; color: #0f172a;">Struktur Informasi Terorganisir (Tabel / List)</div>
                    <div style="font-size: 12px; color: #475569;">Tabel data HTML atau daftar bernomor (<code>&lt;ol&gt;</code>).</div>
                </div>
                <div>
                    <span data-ag="chk-struct-pill" class="answergraph-pill" style="<?php echo ($aeo_eval['has_data_table'] || $aeo_eval['has_ordered_list']) ? 'background:#dcfce7;color:#166534;' : 'background:#fef3c7;color:#92400e;'; ?>">
                        <?php echo ($aeo_eval['has_data_table'] || $aeo_eval['has_ordered_list']) ? 'Lengkap' : 'Disarankan'; ?>
                    </span>
                </div>
            </div>

            <div class="answergraph-checklist-row">
                <div>&bull;</div>
                <div>
                    <div style="font-weight: 600; color: #0f172a;">Tautan Fakta Otoritatif Keluar</div>
                    <div style="font-size: 12px; color: #475569;">Terdeteksi <strong><span data-ag="chk-ext-num"><?php echo intval($geo_eval['external_citations_count']); ?></span> rujukan eksternal</strong> (JDIH/BPK/ESDM).</div>
                </div>
                <div>
                    <span data-ag="chk-ext-pill" class="answergraph-pill" style="<?php echo $geo_eval['external_citations_count'] >= 1 ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#991b1b;'; ?>">
                        <?php echo $geo_eval['external_citations_count'] >= 1 ? 'Lolos' : 'Perlu Rujukan'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Dantara ESDM Regulatory & Anti-Slop Linter -->
        <div style="border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden;">
            <div style="background: #f8fafc; padding: 10px 14px; font-weight: 700; font-size: 12px; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;">
                Linter Regulasi ESDM &amp; Anti-AI-Slop (Dantara Mandiri)
            </div>
            <div data-ag="lint-container" style="padding: 12px 14px;">
                <?php if (empty($lint_issues)) : ?>
                    <p style="margin: 0; color: #166534; font-weight: 600;">Tidak ditemukan pelanggaran regulasi ESDM atau frasa klise AI.</p>
                <?php else : ?>
                    <ul style="margin: 0 0 0 18px; padding: 0;">
                        <?php foreach ($lint_issues as $iss) :
                            $c = $iss['severity'] === 'error' ? '#991b1b' : ($iss['severity'] === 'warning' ? '#854d0e' : '#0369a1');
                        ?>
                            <li style="color: <?php echo esc_attr($c); ?>; margin-bottom: 4px;"><?php echo esc_html($iss['message']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pillar Internal Link Suggestions -->
        <div style="border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; margin-top: 12px;">
            <div style="background: #f8fafc; padding: 10px 14px; font-weight: 700; font-size: 12px; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;">
                Saran Link Internal Pilar
            </div>
            <div data-ag="pillar-container" style="padding: 12px 14px;">
                <?php if (empty($pillar_list)) : ?>
                    <p style="margin: 0; color: #475569;">Tidak ada peluang link internal pilar yang belum tertaut di artikel ini.</p>
                <?php else : ?>
                    <ul style="margin: 0; padding: 0; list-style: none;">
                        <?php foreach ($pillar_list as $opp) : ?>
                            <li style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                                <div>
                                    <strong><?php echo esc_html($opp['anchor']); ?></strong>
                                    <span style="color: #64748b; font-size: 12px;">&rarr; <?php echo esc_html($opp['url']); ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <button type="button" class="button button-small answergraph-copy-pillar" data-snippet="<?php echo esc_attr($opp['snippet']); ?>">Salin HTML Link</button>
                                    <span class="answergraph-copy-status" aria-live="polite" style="font-size: 11px; color: #166534; font-weight: 600;"></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TAB 3: SCHEMA SELECTOR & MARKDOWN VIEW -->
    <div id="answergraph-tab-tools" class="answergraph-tab-content" style="display: none;">
        <?php if (function_exists('wp_nonce_field')) wp_nonce_field('answergraph_save_meta', 'answergraph_meta_nonce'); ?>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 16px; margin-bottom: 14px;">
            <label for="answergraph_schema_type" style="display: block; font-weight: 700; font-size: 13px; color: #0f172a; margin-bottom: 6px;">
                Tipe Schema JSON-LD Tambahan untuk Halaman Ini
            </label>
            <select id="answergraph_schema_type" name="answergraph_schema_type" style="min-width: 280px; cursor: pointer;">
                <option value="default" <?php echo $current_schema === 'default' ? 'selected' : ''; ?>>Default (Gunakan Schema Yoast / WebPage)</option>
                <option value="course" <?php echo $current_schema === 'course' ? 'selected' : ''; ?>>Course (Untuk halaman Bimtek / Jadwal Pelatihan)</option>
                <option value="service" <?php echo $current_schema === 'service' ? 'selected' : ''; ?>>Service (Untuk halaman Konsultasi / LSK SKTTK)</option>
            </select>
            <p style="font-size: 12px; color: #475569; margin: 6px 0 0 0;">
                Pilih lalu klik Update/Publish artikel untuk menyuntikkan JSON-LD `Course` atau `Service` secara otomatis.
            </p>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 16px;">
            <div style="font-weight: 700; font-size: 13px; color: #0f172a; margin-bottom: 4px;">
                Tampilan Token Bersih untuk AI (Zero Bloat Markdown)
            </div>
            <p style="font-size: 12px; color: #475569; margin: 0 0 12px 0;">
                Endpoint <code>?format=llm-md</code> menyajikan teks dan tabel bersih untuk crawler AI.
            </p>
            <a href="<?php echo esc_url($md_url); ?>" target="_blank" rel="noopener" class="button button-secondary" style="font-size: 12px; font-weight: 600; cursor: pointer;">
                Buka Pratinjau Markdown AI
            </a>
        </div>
    </div>

    <!-- Actionable Recommendations Box -->
    <div data-ag="rec-box" style="background: #fffbeb; border: 1px solid #fef08a; border-radius: 6px; padding: 12px 14px; margin-top: 14px;<?php echo empty($geo_eval['recommendations']) ? ' display: none;' : ''; ?>">
        <div style="font-size: 12px; font-weight: 700; color: #854d0e; margin-bottom: 4px;">
            Saran Perbaikan Cepat untuk Penulis:
        </div>
        <ul data-ag="rec-list" style="margin: 0 0 0 20px; padding: 0; font-size: 12px; color: #713f12; line-height: 1.5;">
            <?php foreach ($geo_eval['recommendations'] as $rec) : ?>
                <li style="margin-bottom: 2px;"><?php echo esc_html($rec); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <script>
    function answergraphSwitchTab(button, tabId) {
        var root = button.closest('.answergraph-metabox-root');
        root.querySelectorAll('.answergraph-tab-btn').forEach(function(btn) { btn.classList.remove('active'); });
        root.querySelectorAll('.answergraph-tab-content').forEach(function(content) { content.style.display = 'none'; });
        button.classList.add('active');
        var activeContent = root.querySelector('#' + tabId);
        if (activeContent) activeContent.style.display = 'block';
    }

    (function() {
        var root = document.querySelector('.answergraph-metabox-root');
        if (!root) return;

        var triggers = ['apa', 'apakah', 'bagaimana', 'mengapa', 'kenapa', 'syarat', 'biaya', 'cara', 'aturan', 'kriteria', 'daftar', 'what', 'how', 'why', 'who', 'requirements', 'process'];
        var siteHost = (window.location && window.location.hostname) ? window.location.hostname.toLowerCase() : '';
        var slug = (root.getAttribute('data-ag-slug') || '').toLowerCase();
        var hasElementor = root.getAttribute('data-ag-elementor') === '1';
        var presetEsdm = root.getAttribute('data-ag-preset-esdm') !== '0';
        var customBanned = [];
        try {
            var parsedBanned = JSON.parse(root.getAttribute('data-ag-custom-banned') || '[]');
            if (Array.isArray(parsedBanned)) customBanned = parsedBanned;
        } catch (e) {}
        var timer = null;
        var lastSnapshot = null;

        root.addEventListener('click', function(ev) {
            var btn = ev.target && ev.target.closest ? ev.target.closest('.answergraph-copy-pillar') : null;
            if (!btn) return;
            var snippet = btn.getAttribute('data-snippet') || '';
            var statusEl = btn.parentElement ? btn.parentElement.querySelector('.answergraph-copy-status') : null;
            function markCopied() {
                if (!statusEl) return;
                statusEl.textContent = 'Tersalin!';
                setTimeout(function() { statusEl.textContent = ''; }, 2000);
            }
            function fallbackCopy() {
                var ta = document.createElement('textarea');
                ta.value = snippet;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try { if (document.execCommand('copy')) markCopied(); } catch (e) {}
                document.body.removeChild(ta);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(snippet).then(markCopied, fallbackCopy);
            } else {
                fallbackCopy();
            }
        });

        function stripTags(html) {
            var rawHtml = html || '';
            var text = '';
            if (typeof DOMParser !== 'undefined') {
                var doc = new DOMParser().parseFromString(rawHtml, 'text/html');
                text = (doc && doc.body && doc.body.textContent) ? doc.body.textContent : '';
            } else {
                text = rawHtml.replace(/<[^>]*>/g, ' ');
            }
            return text.replace(/\s+/g, ' ').trim();
        }

        function runLinter(raw, text) {
            var issues = [];
            if (!text) return issues;

            if (presetEsdm) {
                if (/\bPasal\s+4\b/i.test(text) && /(SMK2|5\s*MW|200\s*kVA)/i.test(text)) {
                    issues.push({ severity: 'error', message: 'Menyebut "Pasal 4" untuk kewajiban/ambang SMK2. Gunakan Pasal 26 ayat (1) dan Pasal 28 ayat (2) Permen ESDM No. 10/2021.' });
                }
                if (/(di\s+atas\s+5\s*MW|lebih\s+dari\s+5\s*MW|>\s*5\s*MW|&gt;\s*5\s*MW)/i.test(raw)) {
                    issues.push({ severity: 'error', message: 'Menulis "> 5 MW" atau "di atas/lebih dari 5 MW". Gunakan "paling kecil 5 MW (>= 5 MW)" termasuk tepat 5 MW.' });
                }
                if (/SKTTK[^.]{0,80}\b5\s*(?:\(lima\)\s*)?tahun\b|\b5\s*(?:\(lima\)\s*)?tahun\b[^.]{0,80}SKTTK/i.test(text)) {
                    issues.push({ severity: 'error', message: 'Menyebut masa berlaku SKTTK 5 tahun. Masa berlaku SKTTK resmi adalah 3 (tiga) tahun.' });
                }
                if (/SMK2[^.]{0,120}Permenaker|Permenaker[^.]{0,120}(?:mengatur|mewajibkan|dasar)\s+SMK2/i.test(text)) {
                    issues.push({ severity: 'error', message: 'Mengaitkan SMK2 dengan Permenaker. SMK2 berada mutlak di bawah Permen ESDM No. 10/2021.' });
                }
                if (/LSSM-067-IDN/i.test(text) && !/(LSSM\s+terakreditasi\s+KAN|Evodia)/i.test(text)) {
                    issues.push({ severity: 'warning', message: 'LSSM-067-IDN adalah akreditasi KAN milik LSSM penerbit sertifikat ISO 9001:2015 (PT Evodia Global Sertifikasi), bukan milik Dantara.' });
                }
                if (/(didukung|tenaga\s+ahli|instruktur)[^.]{0,80}Inspektur\s+Ketenagalistrikan/i.test(text)) {
                    issues.push({ severity: 'warning', message: 'Mengklaim "Inspektur Ketenagalistrikan" sebagai tenaga ahli internal berisiko konflik kepentingan.' });
                }
                if (slug !== 'bimtek-smk2' && /\b(SMK2|SKTTK|PJK2)\b/i.test(text) && raw.toLowerCase().indexOf('/bimtek-smk2') === -1) {
                    issues.push({ severity: 'info', message: 'Belum ada internal link kontekstual ke halaman pilar /bimtek-smk2.' });
                }
            }
            var cliches = ['di era digital yang serba dinamis', 'tidak dapat dipungkiri bahwa', 'solusi mutakhir', 'secara keseluruhan, dapat disimpulkan', 'secara keseluruhan, dapat ditarik kesimpulan'];
            for (var i = 0; i < cliches.length; i++) {
                if (text.toLowerCase().indexOf(cliches[i]) !== -1) {
                    issues.push({ severity: 'warning', message: 'Terdeteksi frasa klise AI ("' + cliches[i] + '"). Ganti dengan kalimat teknis spesifik.' });
                    break;
                }
            }
            for (var j = 0; j < customBanned.length; j++) {
                var term = String(customBanned[j] || '').trim();
                if (term && text.toLowerCase().indexOf(term.toLowerCase()) !== -1) {
                    issues.push({ severity: 'warning', message: 'Terdeteksi kata terlarang kustom ("' + term + '").' });
                }
            }
            return issues;
        }

        function evaluateHtml(html) {
            var raw = html || '';
            var text = stripTags(raw);

            var statPatterns = [
                /\b\d+([.,]\d+)?\s*%/gi,
                /\b\d+\s*x\s*(lipat)?\b/gi,
                /\b(Rp|IDR|\$)\s*[\d.,]+/gi,
                /\b\d+\s*(tahun|bulan|hari|peserta|perusahaan|ribu|juta|miliar)\b/gi
            ];
            var statsCount = 0;
            statPatterns.forEach(function(re) {
                var m = text.match(re);
                if (m) statsCount += m.length;
            });

            var quotesCount = 0;
            var bq = raw.match(/<blockquote\b[^>]*>[\s\S]*?<\/blockquote>/gi);
            if (bq) quotesCount += bq.length;
            var iq = text.match(/["\u201C][^"\u201D]+["\u201D]\s*(?:ujar|kata|ungkap|jelas|tutur|sebut|menurut|tegas)/gi);
            if (iq) quotesCount += iq.length;

            var extCount = 0;
            var hrefRe = /<a\b[^>]*href=["']([^"']*)["'][^>]*>/gi;
            var hm;
            while ((hm = hrefRe.exec(raw)) !== null) {
                var u = hm[1];
                if (u.indexOf('http') === 0) {
                    try {
                        var h = new URL(u).hostname.toLowerCase();
                        if (h && (!siteHost || h.indexOf(siteHost) === -1)) extCount++;
                    } catch (e) {}
                }
            }

            var geoScore = Math.min(40, statsCount * 15) + Math.min(35, quotesCount * 20) + Math.min(25, extCount * 15);
            var recs = [];
            if (statsCount < 2) recs.push('Tambahkan minimal 2 data statistik/angka spesifik (misal persentase, rasio, atau angka terukur) untuk meningkatkan kredibilitas sitasi AI.');
            if (quotesCount < 1) recs.push('Tambahkan kutipan ahli industri atau pernyataan otoritatif (blockquote/tanda kutip) untuk memperkuat atribusi AI.');
            if (extCount < 1) recs.push('Sematkan tautan rujukan resmi (regulasi, jurnal, atau instansi pemerintah) sebagai dasar klaim fakta.');

            var hasDirect = false;
            var qaRe = /<(h[23])\b[^>]*>([\s\S]*?)<\/\1>\s*(?:<p\b[^>]*>([\s\S]*?)<\/p>|([^<\r\n][^\r\n]*(?:\r?\n(?![<\r\n])[^\r\n]+)*))/gi;
            var qm;
            while ((qm = qaRe.exec(raw)) !== null) {
                var heading = stripTags(qm[2]).toLowerCase();
                var isQ = triggers.some(function(t) { return new RegExp('\\b' + t + '\\b', 'i').test(heading); });
                if (isQ) {
                    var para = stripTags(qm[3] || qm[4] || '');
                    var words = para ? para.split(/\s+/).length : 0;
                    if (words >= 35 && words <= 65) {
                        hasDirect = true;
                        break;
                    }
                }
            }

            var hasTable = /<table\b[^>]*>/i.test(raw);
            var hasOl = /<ol\b[^>]*>/i.test(raw);
            var aeoStatus = (hasDirect && (hasTable || hasOl)) ? 'green' : (hasDirect ? 'orange' : 'red');

            return {
                stats: statsCount,
                quotes: quotesCount,
                ext: extCount,
                geoScore: geoScore,
                recs: recs,
                aeoStatus: aeoStatus,
                hasDirect: hasDirect,
                hasTable: hasTable,
                hasOl: hasOl,
                lintIssues: runLinter(raw, text)
            };
        }

        function setPill(el, ok, okText, failText, warnBg) {
            if (!el) return;
            el.textContent = ok ? okText : failText;
            el.style.background = ok ? '#dcfce7' : (warnBg ? '#fef3c7' : '#fee2e2');
            el.style.color = ok ? '#166534' : (warnBg ? '#92400e' : '#991b1b');
        }

        function renderResult(res) {
            var geoTok = res.geoScore >= 70
                ? { bg: '#f0fdf4', border: '#86efac', text: '#166534', bar: '#16a34a', label: 'Sangat Siap' }
                : (res.geoScore >= 40
                    ? { bg: '#fefce8', border: '#fde047', text: '#854d0e', bar: '#ca8a04', label: 'Cukup Siap' }
                    : { bg: '#fef2f2', border: '#fca5a5', text: '#991b1b', bar: '#dc2626', label: 'Perlu Optimasi' });

            var aeoTok = res.aeoStatus === 'green'
                ? { bg: '#f0fdf4', border: '#86efac', text: '#166534', pill: '#15803d', title: 'Optimal (40-60 Kata)', desc: 'Definisi padat terdeteksi di bawah subjudul. Memenuhi standar Featured Snippet Google & AI Overview.' }
                : (res.aeoStatus === 'orange'
                    ? { bg: '#fefce8', border: '#fde047', text: '#854d0e', pill: '#ca8a04', title: 'Cukup (Perlu Tabel/List)', desc: 'Teks definisi ditemukan, namun perlu dilengkapi tabel data atau daftar bernomor (<ol>) agar mudah diekstraksi LLM.' }
                    : { bg: '#fef2f2', border: '#fca5a5', text: '#991b1b', pill: '#dc2626', title: 'Belum Ada Direct Answer', desc: 'Belum ditemukan paragraf rangkuman 40-60 kata tepat di bawah heading pertanyaan (H2/H3).' });

            var q = function(sel) { return root.querySelector('[data-ag="' + sel + '"]'); };
            if (q('geo-card')) {
                q('geo-card').style.background = geoTok.bg;
                q('geo-card').style.borderColor = geoTok.border;
            }
            if (q('geo-score-text')) q('geo-score-text').style.color = geoTok.text;
            if (q('geo-score-num')) q('geo-score-num').textContent = res.geoScore;
            if (q('geo-pill')) {
                q('geo-pill').textContent = geoTok.label;
                q('geo-pill').style.color = geoTok.text;
                q('geo-pill').style.borderColor = geoTok.border;
            }
            if (q('geo-progressbar')) q('geo-progressbar').setAttribute('aria-valuenow', res.geoScore);
            if (q('geo-bar')) {
                q('geo-bar').style.width = res.geoScore + '%';
                q('geo-bar').style.background = geoTok.bar;
            }

            if (q('aeo-card')) {
                q('aeo-card').style.background = aeoTok.bg;
                q('aeo-card').style.borderColor = aeoTok.border;
            }
            if (q('aeo-title')) {
                q('aeo-title').textContent = aeoTok.title;
                q('aeo-title').style.color = aeoTok.text;
            }
            if (q('aeo-dot')) q('aeo-dot').style.background = aeoTok.pill;
            if (q('aeo-desc')) q('aeo-desc').textContent = aeoTok.desc;

            if (q('metric-stats')) q('metric-stats').textContent = res.stats;
            if (q('metric-quotes')) q('metric-quotes').textContent = res.quotes;
            if (q('metric-ext')) q('metric-ext').textContent = res.ext;

            if (q('chk-stats-num')) q('chk-stats-num').textContent = res.stats;
            if (q('chk-quotes-num')) q('chk-quotes-num').textContent = res.quotes;
            if (q('chk-ext-num')) q('chk-ext-num').textContent = res.ext;
            setPill(q('chk-stats-pill'), res.stats >= 2, 'Lolos', 'Perlu Tambahan', false);
            setPill(q('chk-quotes-pill'), res.quotes >= 1, 'Lolos', 'Belum Ada', false);
            setPill(q('chk-direct-pill'), res.hasDirect, 'Optimal', 'Perlu Diisi', false);
            setPill(q('chk-struct-pill'), (res.hasTable || res.hasOl), 'Lengkap', 'Disarankan', true);
            setPill(q('chk-ext-pill'), res.ext >= 1, 'Lolos', 'Perlu Rujukan', false);

            // Render Linter issues
            var badge = q('lint-badge');
            if (badge) {
                badge.textContent = res.lintIssues.length;
                badge.style.background = res.lintIssues.length > 0 ? '#fee2e2' : '#dcfce7';
                badge.style.color = res.lintIssues.length > 0 ? '#991b1b' : '#166534';
            }
            var lintBox = q('lint-container');
            if (lintBox) {
                lintBox.innerHTML = '';
                if (res.lintIssues.length === 0) {
                    var okP = document.createElement('p');
                    okP.style.margin = '0';
                    okP.style.color = '#166534';
                    okP.style.fontWeight = '600';
                    okP.textContent = 'Tidak ditemukan pelanggaran regulasi ESDM atau frasa klise AI.';
                    lintBox.appendChild(okP);
                } else {
                    var ul = document.createElement('ul');
                    ul.style.margin = '0 0 0 18px';
                    ul.style.padding = '0';
                    res.lintIssues.forEach(function(iss) {
                        var li = document.createElement('li');
                        li.style.marginBottom = '4px';
                        li.style.color = iss.severity === 'error' ? '#991b1b' : (iss.severity === 'warning' ? '#854d0e' : '#0369a1');
                        li.textContent = iss.message;
                        ul.appendChild(li);
                    });
                    lintBox.appendChild(ul);
                }
            }

            var recBox = q('rec-box');
            var recList = q('rec-list');
            if (recBox && recList) {
                if (res.recs.length === 0) {
                    recBox.style.display = 'none';
                } else {
                    recBox.style.display = 'block';
                    recList.innerHTML = '';
                    res.recs.forEach(function(r) {
                        var li = document.createElement('li');
                        li.style.marginBottom = '2px';
                        li.textContent = r;
                        recList.appendChild(li);
                    });
                }
            }
        }

        function getEditorContent() {
            if (window.wp && wp.data && wp.data.select) {
                var ed = wp.data.select('core/editor');
                if (ed && typeof ed.getEditedPostContent === 'function') {
                    var gb = ed.getEditedPostContent();
                    if (typeof gb === 'string') return gb;
                }
            }
            var textarea = document.getElementById('content');
            if (window.tinymce && tinymce.get && tinymce.get('content') && !tinymce.get('content').isHidden()) {
                return tinymce.get('content').getContent() || '';
            }
            if (textarea) {
                return textarea.value || '';
            }
            return null;
        }

        window.answergraphRunLiveAnalysis = function(force, isInitial) {
            var html = getEditorContent();
            if (html === null) return;
            if (isInitial && hasElementor && html === '') return;
            if (!force && html === lastSnapshot) return;
            lastSnapshot = html;
            renderResult(evaluateHtml(html));
        };

        function scheduleRun() {
            clearTimeout(timer);
            timer = setTimeout(function() { window.answergraphRunLiveAnalysis(false, false); }, 200);
        }

        var ta = document.getElementById('content');
        if (ta) {
            ['input', 'change', 'keyup', 'paste', 'cut'].forEach(function(evt) {
                ta.addEventListener(evt, scheduleRun);
            });
        }

        function bindTinyMCE() {
            if (!window.tinymce || !tinymce.get) return false;
            var ed = tinymce.get('content');
            if (!ed) return false;
            ['keyup', 'change', 'SetContent', 'Paste', 'Cut', 'input'].forEach(function(evt) {
                ed.on(evt, scheduleRun);
            });
            return true;
        }
        if (!bindTinyMCE()) {
            var tries = 0;
            var tmTimer = setInterval(function() {
                tries++;
                if (bindTinyMCE() || tries > 20) clearInterval(tmTimer);
            }, 500);
        }

        if (window.wp && wp.data && wp.data.subscribe) {
            wp.data.subscribe(scheduleRun);
        }

        setTimeout(function() { window.answergraphRunLiveAnalysis(false, true); }, 600);
    })();
    </script>
</div>
