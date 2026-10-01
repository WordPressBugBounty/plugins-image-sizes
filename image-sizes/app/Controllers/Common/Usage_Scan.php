<?php
namespace Codexpert\ThumbPress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Models\Hash_Index;
use Codexpert\ThumbPress\Traits\Hook;

/**
 * Finds the images nothing seems to use, in the background, after a library scan finishes.
 *
 * One forward pass per source, never a lookup per image: every source is read in ID order and the
 * file names it mentions are marked in the index. Anything left unmarked is "likely unused" - an
 * estimate, because a page builder or a theme can reference an image in ways no source here sees.
 */
class Usage_Scan {

	use Hook;

	const ACTION = 'thumbpress_scan_image_usage';

	/**
	 * Where the pass has got to: { phase, cursor, max }.
	 */
	const STATE_OPTION = 'thumbpress_usage_state';

	/**
	 * Unix time the last pass finished; below the scan's own completion time the estimate is stale.
	 */
	const DONE_OPTION = 'thumbpress_usage_scanned_at';

	/**
	 * Set before the queue is cleared so an executing batch stops re-scheduling.
	 */
	const CANCEL_OPTION = 'thumbpress_usage_cancelled';

	/**
	 * Seconds between batches.
	 */
	const BATCH_DELAY = 30;

	/**
	 * Seconds a batch may spend - half of Action Scheduler's 30s budget.
	 */
	const BATCH_TIME_BUDGET = 15;

	const PHASES = array( 'parents', 'ids', 'content', 'meta', 'options' );

	const PARENT_BATCH  = 5000;
	const ID_BATCH      = 2000;
	const CONTENT_BATCH = 100;
	const WINDOW        = 20000;

	/**
	 * Meta that holds attachment IDs rather than URLs.
	 */
	const ID_META_KEYS = array( '_thumbnail_id', '_product_image_gallery' );

	public function __construct() {
		$this->action( 'thumbpress_library_scanned', array( $this, 'start' ) );
		$this->action( self::ACTION, array( $this, 'run' ) );
	}

	/**
	 * Begin a pass, discarding whatever an earlier one left behind.
	 */
	public function start() {
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		as_unschedule_all_actions( self::ACTION );
		delete_option( self::CANCEL_OPTION );
		delete_option( self::DONE_OPTION );

		Hash_Index::reset_usage();

		update_option( self::STATE_OPTION, array( 'phase' => self::PHASES[0], 'cursor' => 0, 'max' => 0 ), false );

		as_schedule_single_action( wp_date( 'U' ) - 10, self::ACTION );
	}

	/**
	 * Stop a pass; the flag is set first so an executing batch stops too.
	 */
	public static function abort() {
		update_option( self::CANCEL_OPTION, true, false );

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::ACTION );
		}

		delete_option( self::STATE_OPTION );
	}

	/**
	 * Whether the estimate describes the library as it was last scanned.
	 *
	 * @return bool
	 */
	public static function is_current() {
		$done = (int) get_option( self::DONE_OPTION, 0 );

		return $done > 0 && $done >= (int) get_option( 'thumbpress_scan_completed_at', 0 );
	}

	/**
	 * Seconds this batch may spend.
	 */
	protected function batch_time_budget() {
		return self::BATCH_TIME_BUDGET;
	}

	/**
	 * Action Scheduler callback: work through the sources until the budget is spent.
	 *
	 * Each source moves a cursor forward by ID, so a row is read at most once and the pass ends. The
	 * budget is checked after a step, so every batch advances the cursor by at least one step.
	 */
	public function run() {
		if ( get_option( self::CANCEL_OPTION, false ) ) {
			return;
		}

		$state = get_option( self::STATE_OPTION );

		if ( ! is_array( $state ) || ! in_array( $state['phase'] ?? '', self::PHASES, true ) ) {
			return;
		}

		$deadline = microtime( true ) + $this->batch_time_budget();

		do {
			$state = $this->step( $state );

			if ( null === $state ) {
				$this->finish();
				return;
			}

			update_option( self::STATE_OPTION, $state, false );
		} while ( microtime( true ) < $deadline );

		if ( ! get_option( self::CANCEL_OPTION, false ) ) {
			as_schedule_single_action( wp_date( 'U' ) + self::BATCH_DELAY, self::ACTION );
		}
	}

	/**
	 * Flag the pass as finished.
	 */
	private function finish() {
		delete_option( self::STATE_OPTION );
		update_option( self::DONE_OPTION, (int) wp_date( 'U' ), false );
	}

	/**
	 * One slice of one source.
	 *
	 * @param array $state Where the pass has got to.
	 * @return array|null The next state, or null when every source has been read.
	 */
	private function step( array $state ) {
		$done = call_user_func( array( $this, 'read_' . $state['phase'] ), $state );

		if ( false === $done['finished'] ) {
			$state['cursor'] = $done['cursor'];
			$state['max']    = $done['max'] ?? $state['max'];

			return $state;
		}

		$next = array_search( $state['phase'], self::PHASES, true ) + 1;

		if ( ! isset( self::PHASES[ $next ] ) ) {
			return null;
		}

		return array( 'phase' => self::PHASES[ $next ], 'cursor' => 0, 'max' => 0 );
	}

	/**
	 * Images attached to a post were uploaded for it.
	 *
	 * @param array $state Pass state.
	 * @return array
	 */
	private function read_parents( array $state ) {
		global $wpdb;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				 WHERE post_type = 'attachment' AND post_parent > 0 AND ID > %d
				 ORDER BY ID ASC LIMIT %d",
				(int) $state['cursor'],
				self::PARENT_BATCH
			)
		);

		Hash_Index::mark_used_ids( $ids );

		return $this->keyset( $ids, self::PARENT_BATCH, $state );
	}

	/**
	 * Featured images, product galleries and the site's own logo and icon are stored as IDs.
	 *
	 * @param array $state Pass state.
	 * @return array
	 */
	private function read_ids( array $state ) {
		global $wpdb;

		if ( 0 === (int) $state['cursor'] ) {
			Hash_Index::mark_used_ids( array( get_option( 'site_icon' ), get_option( 'site_logo' ), get_theme_mod( 'custom_logo' ) ) );
		}

		$marks = implode( ',', array_fill( 0, count( self::ID_META_KEYS ), '%s' ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, meta_value FROM {$wpdb->postmeta}
				 WHERE meta_key IN ( {$marks} ) AND meta_id > %d
				 ORDER BY meta_id ASC LIMIT %d",
				array_merge( self::ID_META_KEYS, array( (int) $state['cursor'], self::ID_BATCH ) )
			)
		);

		$used = array();

		foreach ( $rows as $row ) {
			foreach ( explode( ',', (string) $row->meta_value ) as $id ) {
				$used[] = $id;
			}
		}

		Hash_Index::mark_used_ids( $used );

		return $this->keyset( wp_list_pluck( $rows, 'meta_id' ), self::ID_BATCH, $state );
	}

	/**
	 * Posts, pages and every custom type embed images in their content.
	 *
	 * @param array $state Pass state.
	 * @return array
	 */
	private function read_content( array $state ) {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_content FROM {$wpdb->posts}
				 WHERE post_type NOT IN ( 'attachment', 'revision' )
				 AND post_status NOT IN ( 'trash', 'auto-draft' )
				 AND ID > %d
				 ORDER BY ID ASC LIMIT %d",
				(int) $state['cursor'],
				self::CONTENT_BATCH
			)
		);

		$bases = array();

		foreach ( $rows as $row ) {
			$bases = array_merge( $bases, $this->bases_in( $row->post_content ) );
		}

		Hash_Index::mark_used_bases( $bases );

		return $this->keyset( wp_list_pluck( $rows, 'ID' ), self::CONTENT_BATCH, $state );
	}

	/**
	 * Page builders and custom fields keep their images in meta.
	 *
	 * @param array $state Pass state.
	 * @return array
	 */
	private function read_meta( array $state ) {
		global $wpdb;

		$skip = array( '_wp_attachment_metadata', '_wp_attached_file', '_wp_attachment_backup_sizes' );

		return $this->read_window(
			$state,
			$wpdb->postmeta,
			'meta_id',
			'meta_value',
			'AND meta_key NOT IN ( ' . implode( ',', array_fill( 0, count( $skip ), '%s' ) ) . ' )',
			$skip
		);
	}

	/**
	 * Widgets, theme settings and plugin options mention images too.
	 *
	 * @param array $state Pass state.
	 * @return array
	 */
	private function read_options( array $state ) {
		global $wpdb;

		return $this->read_window(
			$state,
			$wpdb->options,
			'option_id',
			'option_value',
			'AND option_name NOT LIKE %s',
			array( $wpdb->esc_like( 'thumbpress_' ) . '%' )
		);
	}

	/**
	 * Read a table a fixed ID window at a time.
	 *
	 * The filter inside a window is only a shortcut: the cursor moves by the window whether or not
	 * anything matched, so a sparse table cannot turn one batch into one very long query.
	 *
	 * @param array  $state  Pass state.
	 * @param string $table  Table.
	 * @param string $id     Primary key column.
	 * @param string $value  Column holding the text to search.
	 * @param string $extra  Extra WHERE condition, with %s placeholders for its arguments.
	 * @param array  $args   Values for those placeholders.
	 * @return array
	 */
	private function read_window( array $state, $table, $id, $value, $extra, array $args ) {
		global $wpdb;

		$max = (int) $state['max'];

		if ( 0 === $max ) {
			$max = (int) $wpdb->get_var( "SELECT MAX( {$id} ) FROM {$table}" ); // phpcs:ignore WordPress.DB
		}

		$from = (int) $state['cursor'];
		$to   = $from + self::WINDOW;

		$like = '%' . $wpdb->esc_like( $this->uploads_folder() . '/' ) . '%';

		$values = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT {$value} FROM {$table} WHERE {$id} > %d AND {$id} <= %d AND {$value} LIKE %s {$extra}", // phpcs:ignore WordPress.DB
				array_merge( array( $from, $to, $like ), $args )
			)
		);

		$bases = array();

		foreach ( $values as $text ) {
			$bases = array_merge( $bases, $this->bases_in( $text ) );
		}

		Hash_Index::mark_used_bases( $bases );

		return array(
			'finished' => $to >= $max,
			'cursor'   => $to,
			'max'      => $max,
		);
	}

	/**
	 * Cursor for an ID-ordered read: the last ID seen, finished when the read came back short.
	 *
	 * @param array $ids   IDs the read returned, ascending.
	 * @param int   $limit Rows the read asked for.
	 * @param array $state Pass state.
	 * @return array
	 */
	private function keyset( array $ids, $limit, array $state ) {
		if ( count( $ids ) < $limit ) {
			return array( 'finished' => true, 'cursor' => (int) $state['cursor'] );
		}

		return array( 'finished' => false, 'cursor' => (int) end( $ids ) );
	}

	/**
	 * Every image name a piece of text mentions, in the form the index stores.
	 *
	 * Reads plain HTML, JSON with escaped slashes and serialized data alike: only the file name
	 * between the last slash and the extension matters.
	 *
	 * @param string $text Post content, meta value or option value.
	 * @return string[]
	 */
	private function bases_in( $text ) {
		if ( ! is_string( $text ) || '' === $text ) {
			return array();
		}

		if ( ! preg_match_all( '/([^\/\\\\\s"\'<>()=,;]+)\.(?:jpe?g|png|gif|webp|avif|bmp)/i', $text, $found ) ) {
			return array();
		}

		return array_map( array( Hash_Index::class, 'base_of' ), $found[0] );
	}

	/**
	 * Name of the uploads folder, so a custom location is still recognised.
	 *
	 * @return string
	 */
	private function uploads_folder() {
		$dir = wp_upload_dir( null, false );

		return wp_basename( $dir['baseurl'] ?? 'uploads' );
	}
}
