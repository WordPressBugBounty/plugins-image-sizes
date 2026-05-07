<?php
namespace Thumbpress\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Generic Database class for handling database operations across different tables.
 */
class Database {

	/**
	 * The WordPress database access abstraction object.
	 *
	 * @var wpdb
	 */
	protected $db;

	/**
	 * The common prefix for the tables.
	 *
	 * @var string
	 */
	protected $prefix = 'thumbpress_';

	/**
	 * The full database table name including the prefix.
	 *
	 * @var string
	 */
	protected $table_name;

	/**
	 * The primary key for the specific table.
	 *
	 * @var string
	 */
	protected $primary_key;

	/**
	 * Constructor to set up database operations.
	 *
	 * @param string $table_name Table name for the specific entity (without prefix).
	 * @param string $primary_key The primary key for the specific table.
	 */
	public function __construct( $table_name = null, $primary_key = 'id' ) {
		global $wpdb;

		$this->db          = $wpdb;
		$this->table_name  = $this->db->prefix . $this->prefix . $table_name;
		$this->primary_key = $primary_key;
	}

	/**
	 * Sets the table name and updates the table name property.
	 *
	 * @param string $table_name Table name for the specific entity (without prefix).
	 * @return void
	 */
	public function set_table( $table_name ) {
		$this->table_name = $this->db->prefix . $this->prefix . $table_name;
	}

	/**
	 * Gets the table name
	 *
	 * @return string $table_name Table name for the specific entity (with prefix).
	 */
	public function get_table() {
		return $this->table_name;
	}

	/**
	 * Gets table data
	 *
	 * @return array $data Table data
	 * @todo rename method and maybve marge it with get_rows
	 */
	public function get_data( $data = '*' ) {
		$query = "SELECT $data FROM {$this->table_name}";
		return $this->db->get_results( $query );
	}

	/**
	 * Creates a new table in the database based on the table name provided during the object's instantiation.
	 *
	 * @param array $columns Associative array of column definitions.
	 * @param array $options Additional options for table creation like primary key.
	 * @return void
	 */
	public function create_table( $columns, $options = array() ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $this->db->get_charset_collate();
		$sql             = "CREATE TABLE {$this->table_name} (\n";

		// Add columns
		foreach ( $columns as $column => $definition ) {
			$sql .= "$column $definition,\n";
		}

		// Add primary key
		if ( ! empty( $options['primary_key'] ) ) {
			$sql .= "PRIMARY KEY ({$options['primary_key']}),\n";
		} else {
			$sql .= "PRIMARY KEY ({$this->primary_key}),\n";
		}

		// Add unique keys
		if ( ! empty( $options['unique_keys'] ) ) {
			foreach ( $options['unique_keys'] as $key => $columns ) {
				$sql .= "UNIQUE KEY $key ($columns),\n";
			}
		}

		// Add indexes
		if ( ! empty( $options['indexes'] ) ) {
			foreach ( $options['indexes'] as $index => $columns ) {
				$sql .= "INDEX $index ($columns),\n";
			}
		}

		// Remove trailing comma
		$sql = rtrim( $sql, ",\n" ) . "\n) $charset_collate;";

		// Execute dbDelta
		dbDelta( $sql );

		// Check if the table exists after creation
		if ( $this->db->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === null ) {
			return false;
		}

		// Add foreign keys
		if ( ! empty( $options['foreign_keys'] ) ) {
			foreach ( $options['foreign_keys'] as $key => $fk_options ) {
				$ref_table  = $fk_options['ref_table'];
				$ref_column = $fk_options['ref_column'];

				// Ensure the referenced table exists
				if ( $this->db->get_var( "SHOW TABLES LIKE '$ref_table'" ) === null ) {
					continue;
				}

				// Generate unique foreign key name
				$unique_key_name = "{$key}_{$this->table_name}";

				// Check if the foreign key already exists
				$foreign_key_exists = $this->db->get_var(
					$this->db->prepare(
						'SELECT CONSTRAINT_NAME 
	                     FROM information_schema.KEY_COLUMN_USAGE 
	                     WHERE TABLE_NAME = %s AND CONSTRAINT_NAME = %s',
						$this->table_name,
						$unique_key_name
					)
				);

				if ( $foreign_key_exists ) {
					continue;
				}

				// Add the foreign key
				$fk_sql = "ALTER TABLE {$this->table_name} 
	                       ADD CONSTRAINT $unique_key_name 
	                       FOREIGN KEY ({$fk_options['column']}) 
	                       REFERENCES {$ref_table} ({$ref_column}) 
	                       ON DELETE {$fk_options['on_delete']} 
	                       ON UPDATE {$fk_options['on_update']};";

				$result = $this->db->query( $fk_sql );
			}
		}

		return true;
	}


	/**
	 * Drops the table from the database.
	 *
	 * @return void
	 */
	public function drop_table() {
		$sql = "DROP TABLE IF EXISTS {$this->table_name}";
		$this->db->query( $sql );
	}

	public function prepare( $query, ...$args ) {
		return $this->db->prepare( $query, ...$args );
	}

	/**
	 * Retrieves an entry by its primary key ID.
	 *
	 * @param int $id The ID of the entry to retrieve.
	 * @return object|null The object if found, otherwise null.
	 */
	public function get_by_id( $id ) {
		$query = $this->db->prepare( "SELECT * FROM {$this->table_name} WHERE {$this->primary_key} = %d", $id );

		return $this->db->get_row( $query );
	}

	/**
	 * Inserts a new entry into the database.
	 *
	 * @param array $data Associative array of data to insert.
	 * @return int|null The last inserted ID or null on failure.
	 */
	public function insert_row( $data ) {
		if ( $this->db->insert( $this->table_name, $data ) ) {
			return $this->db->insert_id;
		}

		return null;
	}

	/**
	 * Updates an existing entry.
	 *
	 * @param int   $id The ID of the entry to update.
	 * @param array $data Associative array of data to update.
	 * @return bool True if successful, false otherwise.
	 */
	public function update_row( $id, array $data ) {
		return $this->db->update( $this->table_name, $data, array( $this->primary_key => $id ) );
	}

	/**
	 * Deletes an entry by its primary key ID.
	 *
	 * @param int $id The ID of the entry to delete.
	 * @return bool True if successful, false otherwise.
	 */
	public function delete_row( $id ) {
		return $this->db->delete( $this->table_name, array( $this->primary_key => $id ) );
	}

	/**
	 * Inserts multiple entries into the database.
	 *
	 * @param array $rows Array of associative arrays of data to insert.
	 * @return array Array of inserted IDs or null on failure.
	 */
	public function insert_rows( $rows ) {
		$inserted_ids = array();
		foreach ( $rows as $data ) {
			$id = $this->insert_row( $data );

			if ( $id === null ) {
				return null;
			}

			$inserted_ids[] = $id;
		}

		return $inserted_ids;
	}

	/**
	 * Updates multiple entries in the database.
	 *
	 * @param array $rows Array of associative arrays containing 'id' and 'data' keys.
	 * @return bool True if all updates are successful, false otherwise.
	 */
	public function update_rows( $rows ) {
		foreach ( $rows as $row ) {
			if ( ! $this->update_row( $row['id'], $row['data'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Deletes multiple entries from the database.
	 *
	 * @param array $ids Array of IDs of the entries to delete.
	 * @return bool True if all deletions are successful, false otherwise.
	 */
	public function delete_rows( $ids ) {
		foreach ( $ids as $id ) {
			if ( ! $this->delete_row( $id ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Retrieves an entry based on associative array of conditions.
	 *
	 * @param array $conditions Associative array of conditions.
	 * @return object|null The object if found, otherwise null.
	 */
	public function get_row( $conditions ) {
		$where_clause = array();
		$values       = array();

		foreach ( $conditions as $key => $value ) {
			$where_clause[] = "$key = %s";
			$values[]       = $value;
		}

		$where_clause = implode( ' AND ', $where_clause );

		$query = $this->db->prepare( "SELECT * FROM {$this->table_name} WHERE $where_clause", ...$values );

		return $this->db->get_row( $query );
	}

	/**
	 * Retrieves multiple entries based on associative array of conditions.
	 *
	 * This method dynamically builds a query to retrieve rows from the database
	 * table, allowing for flexible conditions, sorting, pagination, and nested conditions.
	 *
	 * CONDITIONS EXAMPLES:
	 * - Basic conditions: `[ [ 'status' => 'active' ], [ 'price' => 100 ] ]`
	 *   Produces: `WHERE status = 'active' AND price = 100`
	 * - Nested conditions: `[ [ 'price' => [ '>', 50 ] ] ]`
	 *   Produces: `WHERE price > 50`
	 * - Multiple nested conditions: `[ [ 'price' => [ '>', 50 ] ], [ 'quantity' => [ '<', 20 ] ] ]`
	 *   Produces: `WHERE price > 50 AND quantity < 20`
	 *
	 * SORTING EXAMPLES:
	 * - Default sorting by ID ascending: `'ASC'`
	 * - For descending order: `'DESC'`
	 *
	 * PAGINATION EXAMPLES:
	 * - Limit to 10 rows: `$limit = 10`
	 * - Skip the first 5 rows: `$offset = 5`
	 *
	 * @param array  $conditions Associative array of conditions.
	 *    - For simple conditions: `[ [ 'column' => 'value' ] ]` (e.g., `[ [ 'status' => 'active' ] ]`).
	 *    - For nested conditions: `[ [ 'column' => [ 'operator', value ] ] ]` (e.g., `[ [ 'price' => [ '>', 50 ] ] ]`).
	 *
	 * @param int    $limit The number of rows to return. Defaults to 0 (no limit).
	 * @param int    $offset The number of rows to skip before starting to return results. Defaults to 0.
	 * @param string $order The sorting order ('ASC' or 'DESC'). Defaults to 'ASC'.
	 *
	 * @return array Array of objects if found, otherwise an empty array.
	 *
	 * USAGE EXAMPLES:
	 *
	 * Example 1: Retrieve all active rows with price > 100, sorted descending
	 * $rows = $db->get_rows(
	 *     [
	 *          [ 'status'  => 'active' ],
	 *          [ 'price'   => [ '>', 100 ]
	 *     ],
	 *     10, // Limit
	 *     0,  // Offset
	 *     'DESC' // Order
	 * );
	 *
	 * Example 2: Retrieve rows with multiple conditions and pagination
	 * $rows = $db->get_rows(
	 *     [
	 *         [ 'price'    => [ '>', 50 ] ],
	 *         [ 'quantity' => [ '<', 20 ] ],
	 *     ],
	 *     5,  // Limit
	 *     10, // Offset
	 *     'ASC' // Order
	 * );
	 *
	 * Example 3: Retrieve rows without any conditions (get all)
	 * $rows = $db->get_rows( [], 20, 0, 'ASC');
	 */
	public function get_rows( $conditions = array(), $limit = 0, $offset = 0, $order = 'DESC' ) {
		$where_clause = array();
		$values       = array();

		if ( ! empty( $conditions ) && is_array( $conditions ) ) {

			/**
			 * Ensure 2-level conditions
			 */
			if ( ! is_array( reset( $conditions ) ) ) {
				$conditions = array( $conditions );
			}

			/**
			 * Loop through the conditions to contruct the query
			 */
			foreach ( $conditions as $condition ) {
				if ( is_array( $condition ) ) {
					foreach ( $condition as $column => $value ) {

						// Handle nested conditions like `[ 'key' => [ '>=', 'value' ] ]`.
						if ( is_array( $value ) && count( $value ) == 2 && ! is_array( $value[1] ) ) {
							$operator       = $value[0];
							$_value         = $value[1];
							$placeholder    = is_numeric( $_value ) ? '%d' : '%s';
							$where_clause[] = "{$column} {$operator} {$placeholder}";
							$values[]       = $_value;
						}

						// Handle IN operator explicitly when value is `[ 'IN', [ value1, value2, ... ] ]`.
						elseif ( is_array( $value ) && count( $value ) == 2 && strtoupper( $value[0] ) === 'IN' && is_array( $value[1] ) ) {
							$_values        = $value[1];
							$placeholders   = implode( ', ', array_fill( 0, count( $_values ), is_numeric( reset( $_values ) ) ? '%d' : '%s' ) );
							$where_clause[] = "{$column} IN ({$placeholders})";
							$values         = array_merge( $values, $_values );
						}

						// Handle single conditions like `[ 'key' => 'value' ]`.
						else {
							$placeholder    = is_numeric( $value ) ? '%d' : '%s';
							$where_clause[] = "{$column} = {$placeholder}";
							$values[]       = $value;
						}
					}
				}
			}
		}

		$where_clause_str = ! empty( $where_clause ) ? 'WHERE ' . implode( ' AND ', $where_clause ) : '';

		$order_clause = strtoupper( $order ) === 'DESC' ? ' ORDER BY id DESC' : ' ORDER BY id ASC';

		$query = "SELECT * FROM {$this->table_name} $where_clause_str $order_clause";

		if ( $limit > 0 ) {
			$query   .= ' LIMIT %d';
			$values[] = $limit;

			if ( $offset > 0 ) {
				$query   .= ' OFFSET %d';
				$values[] = $offset;
			}
		}

		$prepared_query = ! empty( $values ) ? $this->db->prepare( $query, ...$values ) : $query;

		return $this->db->get_results( $prepared_query );
	}

	/**
	 * Retrieves the total count of entries based on associative array of conditions.
	 *
	 * @param array $conditions Associative array of conditions.
	 * @return int The count of entries.
	 */
	public function get_count( $conditions = array() ) {
		$where_clause = array();
		$values       = array();

		if ( ! empty( $conditions ) && is_array( $conditions ) ) {
			foreach ( $conditions as $key => $condition ) {
				$where_clause[] = "$key = %s";
				$values[]       = $condition;
			}
		}

		$where_clause_str = ! empty( $where_clause ) ? 'WHERE ' . implode( ' AND ', $where_clause ) : '';
		$query            = "SELECT COUNT(*) FROM {$this->table_name} $where_clause_str";

		$prepared_query = ! empty( $values ) ? $this->db->prepare( $query, ...$values ) : $query;

		return (int) $this->db->get_var( $prepared_query );
	}

	/**
	 * Get the default WordPress table prefix.
	 *
	 * @return string
	 */
	public function get_wp_prefix() {
		return $this->db->prefix;
	}

	/**
	 * Get the table prefix.
	 *
	 * @return string
	 */
	public function get_prefix() {
		return $this->get_wp_prefix() . $this->prefix;
	}

	public function exec( $sql ) {

		return $this->db->get_results( $sql );
	}

	/**
	 * Gets the $db instance
	 */
	public function get_instance() {
		return $this->db;
	}
}
