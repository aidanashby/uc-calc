<?php
/**
 * GitHub-based plugin updater.
 *
 * Polls the GitHub Releases API for `UC_CALC_GITHUB_REPO` and exposes new
 * versions through the standard WordPress plugin update flow, via the
 * `update_plugins_github.com` hook that core fires for plugins whose
 * `Update URI` header points at GitHub. Caches results
 * via transients (12 hours on success, 30 minutes on transient failure) and
 * renames the unpacked archive folder so WordPress installs the update under
 * the existing plugin slug rather than the GitHub-generated `user-repo-sha`
 * directory.
 *
 * Tag releases with semver-style names (e.g. `v1.0.1`). The `v` prefix is
 * stripped before comparison. Optional release-notes headers `Tested up to:`,
 * `Requires at least:`, and `Requires PHP:` are extracted into the WordPress
 * update modal.
 *
 * @package UC_Calc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UC_Calc_Updater {

	private $plugin_file;
	private $plugin_slug;
	private $plugin_dir;
	private $repo;
	private $version;

	private $cache_key       = 'uc_calc_github_release';
	private $cache_ttl       = 12 * HOUR_IN_SECONDS;
	private $cache_ttl_error = 30 * MINUTE_IN_SECONDS;

	public function __construct( $plugin_file, $repo, $version ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_slug = plugin_basename( $plugin_file );
		$this->plugin_dir  = dirname( $this->plugin_slug );
		$this->repo        = $repo;
		$this->version     = $version;

		$host = wp_parse_url( "https://github.com/{$repo}", PHP_URL_HOST );
		add_filter( "update_plugins_{$host}", [ $this, 'filter_update' ], 10, 3 );
		add_filter( 'plugins_api', [ $this, 'plugin_information' ], 20, 3 );
		add_filter( 'upgrader_source_selection', [ $this, 'rename_source' ], 10, 4 );
		add_action( 'upgrader_process_complete', [ $this, 'flush_cache_after_upgrade' ], 10, 2 );
	}

	/**
	 * Supply release data to WordPress core's update check.
	 *
	 * Core calls `update_plugins_{hostname}` for every plugin whose `Update URI`
	 * header points at that host, in admin, cron and WP-CLI alike, then files the
	 * result under `response` (newer) or `no_update` (current). Returning data in
	 * both cases lets the auto-update toggle work.
	 *
	 * @param array|false $update      Update data from earlier filters, or false.
	 * @param array       $plugin_data Plugin headers.
	 * @param string      $plugin_file Plugin basename.
	 * @return array|false
	 */
	public function filter_update( $update, $plugin_data, $plugin_file ) {
		if ( $plugin_file !== $this->plugin_slug ) {
			return $update;
		}

		$release = $this->get_latest_release( $this->is_forced_check() );
		if ( ! $release ) {
			return $update;
		}

		return [
			'slug'         => $this->plugin_dir,
			'version'      => $release['version'],
			'url'          => $release['html_url'],
			'package'      => $release['zip_url'],
			'tested'       => $release['tested'],
			'requires'     => $release['requires'],
			'requires_php' => $release['requires_php'],
			'icons'        => [],
			'banners'      => [],
			'banners_rtl'  => [],
		];
	}

	/**
	 * True when an admin clicked "Check again" on Dashboard → Updates, so the
	 * cached release is bypassed.
	 */
	private function is_forced_check() {
		return is_admin()
			&& current_user_can( 'update_plugins' )
			&& ! empty( $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Provide plugin details for the "View details" modal.
	 */
	public function plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( empty( $args->slug ) || $args->slug !== $this->plugin_dir ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		return (object) [
			'name'          => 'UC Calculator',
			'slug'          => $this->plugin_dir,
			'version'       => $release['version'],
			'author'        => '<a href="https://lucidrhino.design">Aidan Ashby</a>',
			'homepage'      => "https://github.com/{$this->repo}",
			'requires'      => $release['requires'],
			'tested'        => $release['tested'],
			'requires_php'  => $release['requires_php'],
			'download_link' => $release['zip_url'],
			'last_updated'  => $release['published_at'],
			'sections'      => [
				'description' => esc_html__( 'Universal Credit vs essentials calculator. Client-side, no data collected.', 'uc-calc' ),
				'changelog'   => $release['changelog'],
			],
		];
	}

	/**
	 * Rename `aidanashby-uc-calc-abc1234/` to `uc-calc/` during upgrade unpack.
	 */
	public function rename_source( $source, $remote_source, $upgrader, $hook_extra = [] ) {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			return $source;
		}
		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_slug ) {
			return $source;
		}

		$parent   = trailingslashit( dirname( untrailingslashit( $source ) ) );
		$expected = $parent . $this->plugin_dir;

		if ( untrailingslashit( $source ) === untrailingslashit( $expected ) ) {
			return $source;
		}

		if ( $wp_filesystem->move( untrailingslashit( $source ), $expected ) ) {
			return trailingslashit( $expected );
		}

		return $source;
	}

	/**
	 * Clear the release cache after this plugin is upgraded so the next check is fresh.
	 *
	 * Handles both single-plugin (`plugin`) and bulk-upgrade (`plugins`) hook payloads.
	 */
	public function flush_cache_after_upgrade( $upgrader, $extra ) {
		if ( ! is_array( $extra ) || empty( $extra['type'] ) || 'plugin' !== $extra['type'] ) {
			return;
		}

		$plugins = [];
		if ( ! empty( $extra['plugins'] ) && is_array( $extra['plugins'] ) ) {
			$plugins = $extra['plugins'];
		} elseif ( ! empty( $extra['plugin'] ) ) {
			$plugins = [ $extra['plugin'] ];
		}

		if ( in_array( $this->plugin_slug, $plugins, true ) ) {
			delete_transient( $this->cache_key );
		}
	}

	private function get_latest_release( $force = false ) {
		// `false` = no cache entry. Empty array = negative cache (recent failure).
		$cached = $force ? false : get_transient( $this->cache_key );
		if ( false !== $cached ) {
			return ( is_array( $cached ) && ! empty( $cached ) ) ? $cached : null;
		}

		$response = wp_remote_get(
			"https://api.github.com/repos/{$this->repo}/releases/latest",
			[
				'timeout' => 10,
				'headers' => [
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'WordPress/UC-Calc-Updater',
				],
			]
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $this->cache_key, [], $this->cache_ttl_error );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			set_transient( $this->cache_key, [], $this->cache_ttl_error );
			return null;
		}

		$headers  = $this->parse_release_headers( isset( $body['body'] ) ? $body['body'] : '' );
		$release  = [
			'version'      => ltrim( $body['tag_name'], 'vV' ),
			'html_url'     => isset( $body['html_url'] ) ? esc_url_raw( $body['html_url'] ) : '',
			'zip_url'      => $this->find_zip_url( $body ),
			'changelog'    => $this->format_changelog( isset( $body['body'] ) ? $body['body'] : '' ),
			'published_at' => isset( $body['published_at'] ) ? $body['published_at'] : '',
			'tested'       => isset( $headers['tested'] ) ? $headers['tested'] : '',
			'requires'     => isset( $headers['requires'] ) ? $headers['requires'] : '',
			'requires_php' => isset( $headers['requires_php'] ) ? $headers['requires_php'] : '',
		];

		set_transient( $this->cache_key, $release, $this->cache_ttl );
		return $release;
	}

	private function find_zip_url( $release ) {
		// Prefer a release asset zip if one was uploaded.
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( ! empty( $asset['browser_download_url'] ) && '.zip' === substr( $asset['name'], -4 ) ) {
					return esc_url_raw( $asset['browser_download_url'] );
				}
			}
		}
		return ! empty( $release['zipball_url'] ) ? esc_url_raw( $release['zipball_url'] ) : '';
	}

	private function parse_release_headers( $body ) {
		$headers = [];
		if ( preg_match( '/Tested up to:\s*([0-9.]+)/i', $body, $m ) ) {
			$headers['tested'] = trim( $m[1] );
		}
		if ( preg_match( '/Requires at least:\s*([0-9.]+)/i', $body, $m ) ) {
			$headers['requires'] = trim( $m[1] );
		}
		if ( preg_match( '/Requires PHP:\s*([0-9.]+)/i', $body, $m ) ) {
			$headers['requires_php'] = trim( $m[1] );
		}
		return $headers;
	}

	private function format_changelog( $body ) {
		$body = wp_kses_post( $body );
		return wpautop( $body );
	}
}
