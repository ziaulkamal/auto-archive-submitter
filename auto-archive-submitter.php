<?php
/*
Plugin Name: Auto Archive.org Submitter
Plugin URI: https://github.com/ziaulkamal
Description: Automatically send visited pages to Archive.org (Wayback Machine)
Version: 1.0
Author: Ziaul Kamal
Author URI: https://github.com/ziaulkamal
License: GPLv2 or later
*/

if (!defined('ABSPATH')) {
    exit; // Keluar jika diakses langsung
}

class AutoArchiveSubmitter
{
    private $option_name = 'auto_archive_submitter_settings';
    private $log_file = 'auto_archive_submitter.log';
    private $submit_interval = 86400; // 24 jam dalam detik

    public function __construct()
    {
        // Inisialisasi plugin
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Tambahkan menu admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));

        // Proses submit ke Archive.org
        add_action('wp', array($this, 'maybe_submit_to_archive'));
    }

    public function activate()
    {
        // Setelan default saat aktivasi
        $default_options = array(
            'enabled' => '1',
            'log_activity' => '1',
            'submit_interval' => '86400',
            'excluded_urls' => ''
        );

        add_option($this->option_name, $default_options);
    }

    public function deactivate()
    {
        // Bersihkan opsi saat dinonaktifkan
        delete_option($this->option_name);
    }

    public function add_admin_menu()
    {
        add_options_page(
            'Auto Archive.org Submitter',
            'Archive Submitter',
            'manage_options',
            'auto-archive-submitter',
            array($this, 'options_page')
        );
    }

    public function settings_init()
    {
        register_setting('autoArchiveSubmitter', $this->option_name);

        add_settings_section(
            'auto_archive_submitter_section',
            'Pengaturan Auto Archive.org Submitter',
            array($this, 'settings_section_callback'),
            'autoArchiveSubmitter'
        );

        add_settings_field(
            'enabled',
            'Aktifkan Plugin',
            array($this, 'enabled_render'),
            'autoArchiveSubmitter',
            'auto_archive_submitter_section'
        );

        add_settings_field(
            'log_activity',
            'Aktifkan Logging',
            array($this, 'log_activity_render'),
            'autoArchiveSubmitter',
            'auto_archive_submitter_section'
        );

        add_settings_field(
            'submit_interval',
            'Interval Submit (detik)',
            array($this, 'submit_interval_render'),
            'autoArchiveSubmitter',
            'auto_archive_submitter_section'
        );

        add_settings_field(
            'excluded_urls',
            'URL yang Dikecualikan',
            array($this, 'excluded_urls_render'),
            'autoArchiveSubmitter',
            'auto_archive_submitter_section'
        );
    }

    public function enabled_render()
    {
        $options = get_option($this->option_name);
?>
        <input type="checkbox" name="<?php echo $this->option_name; ?>[enabled]" value="1" <?php checked(1, $options['enabled'], true); ?>>
        <p class="description">Centang untuk mengaktifkan plugin</p>
    <?php
    }

    public function log_activity_render()
    {
        $options = get_option($this->option_name);
    ?>
        <input type="checkbox" name="<?php echo $this->option_name; ?>[log_activity]" value="1" <?php checked(1, $options['log_activity'], true); ?>>
        <p class="description">Centang untuk mencatat aktivitas submit</p>
    <?php
    }

    public function submit_interval_render()
    {
        $options = get_option($this->option_name);
    ?>
        <input type="number" name="<?php echo $this->option_name; ?>[submit_interval]" value="<?php echo $options['submit_interval']; ?>" min="3600">
        <p class="description">Interval minimal antara submit untuk URL yang sama (default: 86400 detik = 24 jam)</p>
    <?php
    }

    public function excluded_urls_render()
    {
        $options = get_option($this->option_name);
    ?>
        <textarea name="<?php echo $this->option_name; ?>[excluded_urls]" rows="5" cols="50"><?php echo $options['excluded_urls']; ?></textarea>
        <p class="description">Masukkan URL yang tidak ingin disubmit ke Archive.org (satu URL per baris)</p>
    <?php
    }

    public function settings_section_callback()
    {
        echo '<p>Konfigurasi plugin Auto Archive.org Submitter</p>';
    }

    public function options_page()
    {
    ?>
        <div class="wrap">
            <h1>Auto Archive.org Submitter</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('autoArchiveSubmitter');
                do_settings_sections('autoArchiveSubmitter');
                submit_button();
                ?>
            </form>

            <h2>Log Aktivitas</h2>
            <textarea rows="10" cols="100" readonly><?php
                                                    if (file_exists($this->get_log_path())) {
                                                        echo file_get_contents($this->get_log_path());
                                                    } else {
                                                        echo 'Log file belum ada.';
                                                    }
                                                    ?></textarea>
        </div>
<?php
    }

    private function get_log_path()
    {
        return WP_CONTENT_DIR . '/' . $this->log_file;
    }

    private function log_message($message)
    {
        $options = get_option($this->option_name);
        if (isset($options['log_activity']) && $options['log_activity']) {
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($this->get_log_path(), "[$timestamp] $message\n", FILE_APPEND);
        }
    }

    private function is_url_excluded($url)
    {
        $options = get_option($this->option_name);
        if (empty($options['excluded_urls'])) {
            return false;
        }

        $excluded_urls = explode("\n", $options['excluded_urls']);
        $excluded_urls = array_map('trim', $excluded_urls);
        $excluded_urls = array_filter($excluded_urls);

        foreach ($excluded_urls as $excluded_url) {
            if (strpos($url, $excluded_url) !== false) {
                return true;
            }
        }

        return false;
    }

    private function should_submit_url($url)
    {
        $options = get_option($this->option_name);

        // Cek apakah plugin aktif
        if (!isset($options['enabled']) || !$options['enabled']) {
            return false;
        }

        // Cek apakah URL dikecualikan
        if ($this->is_url_excluded($url)) {
            $this->log_message("URL dikecualikan: $url");
            return false;
        }

        // Cek transien untuk menghindari submit terlalu sering
        $transient_name = 'aas_' . md5($url);
        $last_submit = get_transient($transient_name);

        if ($last_submit !== false) {
            $this->log_message("URL $url sudah disubmit baru-baru ini");
            return false;
        }

        return true;
    }

    public function maybe_submit_to_archive()
    {
        if (is_admin()) {
            return;
        }

        $current_url = home_url($_SERVER['REQUEST_URI']);

        if ($this->should_submit_url($current_url)) {
            $result = $this->submit_to_archive($current_url);

            if ($result['success']) {
                $options = get_option($this->option_name);
                $interval = isset($options['submit_interval']) ? (int)$options['submit_interval'] : $this->submit_interval;

                // Set transien untuk mencegah submit terlalu sering
                set_transient('aas_' . md5($current_url), time(), $interval);

                $this->log_message("Berhasil submit ke Archive.org: $current_url");
                $this->log_message("Wayback URL: " . $result['wayback_url']);
            } else {
                $this->log_message("Gagal submit ke Archive.org: $current_url");
                $this->log_message("Error: HTTP Code " . $result['http_code']);
            }
        }
    }

    private function submit_to_archive($url)
    {
        $wayback_url = 'https://web.archive.org/save/' . urlencode($url);

        $args = array(
            'timeout' => 30,
            'blocking' => false, // Non-blocking request agar tidak memperlambat halaman
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36'
        );

        $response = wp_remote_get($wayback_url, $args);
        $http_code = wp_remote_retrieve_response_code($response);

        return array(
            'success' => ($http_code == 200),
            'http_code' => $http_code,
            'wayback_url' => $wayback_url,
            'timestamp' => date('Y-m-d H:i:s')
        );
    }
}

new AutoArchiveSubmitter();
