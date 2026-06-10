<?php

namespace Khomanguon\TransactionManager;

if (!defined('ABSPATH')) {
    exit;
}

class GitHubUpdater
{
    const GITHUB_OWNER = 'nhockool1002';
    const GITHUB_REPO = 'khomanguon-transaction-manager';
    const CACHE_KEY = 'khomanguon_transaction_manager_github_release';

    private $plugin_file;
    private $plugin_basename;
    private $slug;
    private $current_version;

    public function __construct($plugin_file, $current_version)
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_basename = plugin_basename($plugin_file);
        $this->slug = dirname($this->plugin_basename);
        $this->current_version = $current_version;

        add_filter('update_plugins_github.com', array($this, 'update_from_update_uri'), 10, 4);
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_information'), 10, 3);
        add_filter('upgrader_source_selection', array($this, 'fix_github_source_folder'), 10, 4);
    }

    public function update_from_update_uri($update, $plugin_data, $plugin_file, $locales)
    {
        if ($plugin_file !== $this->plugin_basename) {
            return $update;
        }

        $release = $this->get_latest_package();
        if (!$release) {
            return false;
        }

        return $this->build_update_item($release);
    }

    public function check_for_update($transient)
    {
        if (!is_object($transient) || empty($transient->checked)) {
            return $transient;
        }

        if (!isset($transient->response) || !is_array($transient->response)) {
            $transient->response = array();
        }

        if (!isset($transient->no_update) || !is_array($transient->no_update)) {
            $transient->no_update = array();
        }

        $release = $this->get_latest_package();
        if (!$release) {
            return $transient;
        }

        $latest_version = $this->normalize_version($release['tag_name']);
        $current_version = isset($transient->checked[$this->plugin_basename])
            ? $transient->checked[$this->plugin_basename]
            : $this->current_version;

        if (!version_compare($latest_version, $current_version, '>')) {
            $transient->no_update[$this->plugin_basename] = $this->build_update_item($release);
            return $transient;
        }

        $transient->response[$this->plugin_basename] = $this->build_update_item($release);

        return $transient;
    }

    public function plugin_information($result, $action, $args)
    {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== $this->slug) {
            return $result;
        }

        $release = $this->get_latest_package();
        if (!$release) {
            return $result;
        }

        $body = !empty($release['body'])
            ? nl2br(esc_html($release['body']))
            : esc_html__('Không có changelog cho phiên bản này.', 'khomanguon-transaction-manager');

        return (object) array(
            'name' => 'Khomanguon Transaction Manager',
            'slug' => $this->slug,
            'version' => $this->normalize_version($release['tag_name']),
            'author' => '<a href="https://khomanguon.org">KHOMANGUON.ORG</a>',
            'homepage' => $this->github_repo_url(),
            'download_link' => $release['zipball_url'],
            'last_updated' => isset($release['published_at']) ? $release['published_at'] : '',
            'requires' => '',
            'tested' => get_bloginfo('version'),
            'requires_php' => '',
            'sections' => array(
                'description' => esc_html__('Quản lý giao dịch, ví @Cash, mở khóa S3 và cấu hình cloud cho KHOMANGUON.ORG.', 'khomanguon-transaction-manager'),
                'changelog' => $body,
            ),
        );
    }

    public function fix_github_source_folder($source, $remote_source, $upgrader, $hook_extra)
    {
        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_basename) {
            return $source;
        }

        global $wp_filesystem;

        if (!$wp_filesystem || !$wp_filesystem->is_dir($source)) {
            return $source;
        }

        $correct_source = trailingslashit($remote_source) . $this->slug;
        if (trailingslashit($source) === trailingslashit($correct_source)) {
            return $source;
        }

        if ($wp_filesystem->exists($correct_source)) {
            $wp_filesystem->delete($correct_source, true);
        }

        if ($wp_filesystem->move($source, $correct_source, true)) {
            return $correct_source;
        }

        return $source;
    }

    private function get_latest_package()
    {
        $cached = get_site_transient(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $release = $this->get_latest_release();
        $tag = $this->get_latest_version_tag();

        if ($release && $tag) {
            $release_version = $this->normalize_version($release['tag_name']);
            $tag_version = $this->normalize_version($tag['tag_name']);
            $latest = version_compare($tag_version, $release_version, '>') ? $tag : $release;
        } else {
            $latest = $release ? $release : $tag;
        }

        if (!$latest) {
            return false;
        }

        set_site_transient(self::CACHE_KEY, $latest, 6 * HOUR_IN_SECONDS);

        return $latest;
    }

    private function get_latest_release()
    {
        $release = $this->request_github_json($this->github_release_api_url());
        if (
            !is_array($release)
            || empty($release['tag_name'])
            || empty($release['zipball_url'])
            || !empty($release['draft'])
            || !empty($release['prerelease'])
        ) {
            return false;
        }

        return $release;
    }

    private function get_latest_version_tag()
    {
        $tags = $this->request_github_json($this->github_tags_api_url());
        if (!is_array($tags)) {
            return false;
        }

        $latest = false;
        foreach ($tags as $tag) {
            if (empty($tag['name']) || empty($tag['zipball_url']) || !$this->is_version_tag($tag['name'])) {
                continue;
            }

            $candidate = array(
                'tag_name' => $tag['name'],
                'zipball_url' => $tag['zipball_url'],
                'html_url' => $this->github_tag_url($tag['name']),
                'published_at' => '',
                'body' => '',
            );

            if (
                !$latest
                || version_compare(
                    $this->normalize_version($candidate['tag_name']),
                    $this->normalize_version($latest['tag_name']),
                    '>'
                )
            ) {
                $latest = $candidate;
            }
        }

        return $latest;
    }

    private function request_github_json($url)
    {
        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/vnd.github+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/'),
                ),
            )
        );

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return is_array($data) ? $data : false;
    }

    private function build_update_item(array $release)
    {
        $new_version = $this->normalize_version($release['tag_name']);

        return (object) array(
            'id' => $this->github_repo_url(),
            'slug' => $this->slug,
            'plugin' => $this->plugin_basename,
            'version' => $new_version,
            'new_version' => $new_version,
            'url' => !empty($release['html_url']) ? $release['html_url'] : $this->github_repo_url(),
            'package' => $release['zipball_url'],
            'tested' => get_bloginfo('version'),
            'requires_php' => '',
        );
    }

    private function normalize_version($version)
    {
        return ltrim(trim($version), 'vV');
    }

    private function is_version_tag($tag_name)
    {
        return (bool) preg_match('/^[vV]?\d+(?:\.\d+){0,3}(?:[-+][0-9A-Za-z.-]+)?$/', $tag_name);
    }

    private function github_release_api_url()
    {
        return sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            rawurlencode(self::GITHUB_OWNER),
            rawurlencode(self::GITHUB_REPO)
        );
    }

    private function github_tags_api_url()
    {
        return sprintf(
            'https://api.github.com/repos/%s/%s/tags?per_page=100',
            rawurlencode(self::GITHUB_OWNER),
            rawurlencode(self::GITHUB_REPO)
        );
    }

    private function github_repo_url()
    {
        return sprintf(
            'https://github.com/%s/%s',
            rawurlencode(self::GITHUB_OWNER),
            rawurlencode(self::GITHUB_REPO)
        );
    }

    private function github_tag_url($tag_name)
    {
        return sprintf(
            '%s/tree/%s',
            $this->github_repo_url(),
            rawurlencode($tag_name)
        );
    }
}
