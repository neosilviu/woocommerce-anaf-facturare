<?php
namespace ANAF_Facturare;

class Updater {
    private $plugin;
    private $github_url;
    private $zip_url;
    private $github_api_url;
    private $requires;
    private $tested;
    private $slug;
    private $proper_folder_name;
    private $github_response;

    public function __construct($config = []) {
        $this->plugin = $config['plugin'];
        $this->github_url = $config['github_url'];
        $this->zip_url = $config['zip_url'];
        $this->requires = $config['requires'];
        $this->tested = $config['tested'];
        $this->slug = $config['slug'];
        $this->proper_folder_name = $this->get_repository_name();
        $this->github_api_url = str_replace(
            'https://github.com/',
            'https://api.github.com/repos/',
            $this->github_url
        );

        // Hook into the plugin update system
        $this->init();
    }

    private function init() {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_popup'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
    }

    private function get_repository_name() {
        $repository = parse_url($this->github_url, PHP_URL_PATH);
        $repository = trim($repository, '/');
        $repository = explode('/', $repository);
        return end($repository);
    }

    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $this->get_repository_info();

        if (!empty($this->github_response['tag_name'])) {
            $version = ltrim($this->github_response['tag_name'], 'v');
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $this->plugin);

            if (version_compare($version, $plugin_data['Version'], '>')) {
                $plugin = [
                    'url' => $this->github_url,
                    'slug' => $this->slug,
                    'package' => $this->zip_url,
                    'new_version' => $version,
                    'tested' => $this->tested,
                    'requires' => $this->requires
                ];
                $transient->response[$this->plugin] = (object) $plugin;
            }
        }

        return $transient;
    }

    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== $this->slug) {
            return $result;
        }

        $this->get_repository_info();

        return (object) [
            'name'              => $this->github_response['name'],
            'slug'              => $this->slug,
            'version'           => $this->github_response['tag_name'],
            'author'           => $this->github_response['owner']['login'],
            'homepage'         => $this->github_url,
            'requires'         => $this->requires,
            'tested'           => $this->tested,
            'downloaded'       => 0,
            'last_updated'     => $this->github_response['published_at'],
            'sections'         => [
                'description'  => $this->github_response['body'],
                'changelog'    => $this->get_changelog(),
            ],
            'download_link'    => $this->zip_url
        ];
    }

    private function get_repository_info() {
        if (!empty($this->github_response)) {
            return;
        }

        $response = wp_remote_get($this->github_api_url . '/releases/latest');

        if (is_wp_error($response)) {
            return;
        }

        $this->github_response = json_decode(wp_remote_retrieve_body($response), true);
    }

    private function get_changelog() {
        $response = wp_remote_get($this->github_api_url . '/releases');
        
        if (is_wp_error($response)) {
            return 'No changelog available.';
        }

        $releases = json_decode(wp_remote_retrieve_body($response), true);
        $changelog = '';

        foreach ($releases as $release) {
            $changelog .= "### {$release['tag_name']}\n";
            $changelog .= "{$release['body']}\n\n";
        }

        return $changelog;
    }

    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        $plugin_folder = WP_PLUGIN_DIR . '/' . $this->proper_folder_name;
        $wp_filesystem->move($result['destination'], $plugin_folder);
        $result['destination'] = $plugin_folder;

        // Activate plugin
        activate_plugin($this->plugin);

        return $result;
    }
}
