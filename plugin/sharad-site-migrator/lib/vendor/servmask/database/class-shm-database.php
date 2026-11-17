<?php
/**
 * Copyright (C) 2014-2019 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

abstract class Shm_Database {

	/**
	 * WordPress database handler
	 *
	 * @var object
	 */
	protected $wpdb = null;

	/**
	 * Collations the server supports (lower-case keys), loaded lazily
	 *
	 * @var array
	 */
	protected $supported_collations = null;

	/**
	 * Old table prefixes
	 *
	 * @var array
	 */
	protected $old_table_prefixes = array();

	/**
	 * New table prefixes
	 *
	 * @var array
	 */
	protected $new_table_prefixes = array();

	/**
	 * Old column prefixes
	 *
	 * @var array
	 */
	protected $old_column_prefixes = array();

	/**
	 * New column prefixes
	 *
	 * @var array
	 */
	protected $new_column_prefixes = array();

	/**
	 * Old replace values
	 *
	 * @var array
	 */
	protected $old_replace_values = array();

	/**
	 * New replace values
	 *
	 * @var array
	 */
	protected $new_replace_values = array();

	/**
	 * Raw (unquoted-context) values to search for
	 *
	 * @var array
	 */
	protected $old_replace_raw_values = array();

	/**
	 * Raw values to replace with
	 *
	 * @var array
	 */
	protected $new_replace_raw_values = array();

	/**
	 * Table where clauses
	 *
	 * @var array
	 */
	protected $table_where_clauses = array();

	/**
	 * Table prefix columns
	 *
	 * @var array
	 */
	protected $table_prefix_columns = array();

	/**
	 * Include table prefixes
	 *
	 * @var array
	 */
	protected $include_table_prefixes = array();

	/**
	 * Exclude table prefixes
	 *
	 * @var array
	 */
	protected $exclude_table_prefixes = array();

	/**
	 * List all tables that should not be affected by the timeout of the current request
	 *
	 * @var array
	 */
	protected $atomic_tables = array();

	/**
	 * Visual Composer
	 *
	 * @var boolean
	 */
	protected $visual_composer = false;

	/**
	 * BeTheme Responsive
	 *
	 * @var boolean
	 */
	protected $betheme_responsive = false;

	/**
	 * Optimize Press
	 *
	 * @var boolean
	 */
	protected $optimize_press = false;

	/**
	 * Constructor
	 *
	 * @param object $wpdb WPDB instance
	 */
	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;

		// Check Microsoft SQL Server support
		if ( is_resource( $this->wpdb->dbh ) ) {
			if ( get_resource_type( $this->wpdb->dbh ) === 'SQL Server Connection' ) {
				throw new Shm_Database_Exception(
					'Your WordPress installation uses Microsoft SQL Server. ' .
					'In order to use All in One WP Migration, please change your installation to MySQL and try again. ' .
					''
				);
			}
		}

		// Set database host (HyberDB)
		if ( empty( $this->wpdb->dbhost ) ) {
			if ( isset( $this->wpdb->last_used_server['host'] ) ) {
				$this->wpdb->dbhost = $this->wpdb->last_used_server['host'];
			}
		}

		// Set database name (HyperDB)
		if ( empty( $this->wpdb->dbname ) ) {
			if ( isset( $this->wpdb->last_used_server['name'] ) ) {
				$this->wpdb->dbname = $this->wpdb->last_used_server['name'];
			}
		}
	}

	/**
	 * Set old table prefixes
	 *
	 * @param  array  $prefixes List of table prefixes
	 * @return object
	 */
	public function set_old_table_prefixes( $prefixes ) {
		$this->old_table_prefixes = $prefixes;

		return $this;
	}

	/**
	 * Get old table prefixes
	 *
	 * @return array
	 */
	public function get_old_table_prefixes() {
		return $this->old_table_prefixes;
	}

	/**
	 * Set new table prefixes
	 *
	 * @param  array  $prefixes List of table prefixes
	 * @return object
	 */
	public function set_new_table_prefixes( $prefixes ) {
		$this->new_table_prefixes = $prefixes;

		return $this;
	}

	/**
	 * Get new table prefixes
	 *
	 * @return array
	 */
	public function get_new_table_prefixes() {
		return $this->new_table_prefixes;
	}

	/**
	 * Set old column prefixes
	 *
	 * @param  array  $prefixes List of column prefixes
	 * @return object
	 */
	public function set_old_column_prefixes( $prefixes ) {
		$this->old_column_prefixes = $prefixes;

		return $this;
	}

	/**
	 * Get old column prefixes
	 *
	 * @return array
	 */
	public function get_old_column_prefixes() {
		return $this->old_column_prefixes;
	}

	/**
	 * Set new column prefixes
	 *
	 * @param  array  $prefixes List of column prefixes
	 * @return object
	 */
	public function set_new_column_prefixes( $prefixes ) {
		$this->new_column_prefixes = $prefixes;

		return $this;
	}

	/**
	 * Get new column prefixes
	 *
	 * @return array
	 */
	public function get_new_column_prefixes() {
		return $this->new_column_prefixes;
	}

	/**
	 * Set old replace values
	 *
	 * @param  array  $values List of values
	 * @return object
	 */
	public function set_old_replace_values( $values ) {
		$this->old_replace_values = $values;

		return $this;
	}

	/**
	 * Get old replace values
	 *
	 * @return array
	 */
	public function get_old_replace_values() {
		return $this->old_replace_values;
	}

	/**
	 * Set new replace values
	 *
	 * @param  array  $values List of values
	 * @return object
	 */
	public function set_new_replace_values( $values ) {
		$this->new_replace_values = $values;

		return $this;
	}

	/**
	 * Get new replace values
	 *
	 * @return array
	 */
	public function get_new_replace_values() {
		return $this->new_replace_values;
	}

	/**
	 * Set old replace raw values
	 *
	 * @param  array  $values List of values
	 * @return object
	 */
	public function set_old_replace_raw_values( $values ) {
		$this->old_replace_raw_values = $values;

		return $this;
	}

	/**
	 * Get old replace raw values
	 *
	 * @return array
	 */
	public function get_old_replace_raw_values() {
		return $this->old_replace_raw_values;
	}

	/**
	 * Set new replace raw values
	 *
	 * @param  array  $values List of values
	 * @return object
	 */
	public function set_new_replace_raw_values( $values ) {
		$this->new_replace_raw_values = $values;

		return $this;
	}

	/**
	 * Get new replace raw values
	 *
	 * @return array
	 */
	public function get_new_replace_raw_values() {
		return $this->new_replace_raw_values;
	}

	/**
	 * Set table where clauses
	 *
	 * @param  string $table_name    Table name
	 * @param  array  $where_clauses Table clauses
	 * @return object
	 */
	public function set_table_where_clauses( $table_name, $where_clauses ) {
		$this->table_where_clauses[ strtolower( $table_name ) ] = $where_clauses;

		return $this;
	}

	/**
	 * Get table where clauses
	 *
	 * @param  string $table_name Table name
	 * @return array
	 */
	public function get_table_where_clauses( $table_name ) {
		if ( isset( $this->table_where_clauses[ strtolower( $table_name ) ] ) ) {
			return $this->table_where_clauses[ strtolower( $table_name ) ];
		}

		return array();
	}

	/**
	 * Set table prefix columns
	 *
	 * @param  string $table_name     Table name
	 * @param  array  $prefix_columns Table columns
	 * @return object
	 */
	public function set_table_prefix_columns( $table_name, $prefix_columns ) {
		foreach ( $prefix_columns as $column_name ) {
			$this->table_prefix_columns[ strtolower( $table_name ) ][ strtolower( $column_name ) ] = true;
		}

		return $this;
	}

	/**
	 * Get table prefix columns
	 *
	 * @param  string $table_name Table name
	 * @return array
	 */
	public function get_table_prefix_columns( $table_name ) {
		if ( isset( $this->table_prefix_columns[ strtolower( $table_name ) ] ) ) {
			return $this->table_prefix_columns[ strtolower( $table_name ) ];
		}

		return array();
	}

	/**
	 * Set include table prefixes
	 *
	 * @param  array  $prefixes List of table prefixes
	 * @return object
	 */
	public function set_include_table_prefixes( $prefixes ) {
		$this->include_table_prefixes = $prefixes;

		return $this;
	}

	/**
	 * Get include table prefixes
	 *
	 * @return array
	 */
	public function get_include_table_prefixes() {
		return $this->include_table_prefixes;
	}

	/**
	 * Set exclude table prefixes
	 *
	 * @param  array  $prefixes List of table prefixes
	 * @return object
	 */
	public function set_exclude_table_prefixes( $prefixes ) {
		$this->exclude_table_prefixes = $prefixes;

		return $this;
	}

	/**
	 * Get exclude table prefixes
	 *
	 * @return array
	 */
	public function get_exclude_table_prefixes() {
		return $this->exclude_table_prefixes;
	}

	/**
	 * Set atomic tables
	 *
	 * @param  array  $tables List of tables
	 * @return object
	 */
	public function set_atomic_tables( $tables ) {
		$this->atomic_tables = $tables;

		return $this;
	}

	/**
	 * Get atomic tables
	 *
	 * @return array
	 */
	public function get_atomic_tables() {
		return $this->atomic_tables;
	}

	/**
	 * Set Visual Composer
	 *
	 * @param  boolean $active Is Visual Composer Active?
	 * @return object
	 */
	public function set_visual_composer( $active ) {
		$this->visual_composer = $active;

		return $this;
	}

	/**
	 * Get Visual Composer
	 *
	 * @return boolean
	 */
	public function get_visual_composer() {
		return $this->visual_composer;
	}

	/**
	 * Set BeTheme Responsive
	 *
	 * @param  boolean $active Is BeTheme Responsive Active?
	 * @return object
	 */
	public function set_betheme_responsive( $active ) {
		$this->betheme_responsive = $active;

		return $this;
	}

	/**
	 * Get BeTheme Responsive
	 *
	 * @return boolean
	 */
	public function get_betheme_responsive() {
		return $this->betheme_responsive;
	}

	/**
	 * Set Optimize Press
	 *
	 * @param  boolean $active Is Optimize Press Active?
	 * @return object
	 */
	public function set_optimize_press( $active ) {
		$this->optimize_press = $active;

		return $this;
	}

	/**
	 * Get Optimize Press
	 *
	 * @return boolean
	 */
	public function get_optimize_press() {
		return $this->optimize_press;
	}

	/**
	 * Get tables
	 *
	 * @return array
	 */
	public function get_tables() {
		$tables = array();

		$result = $this->query( "SHOW TABLES FROM `{$this->wpdb->dbname}`" );
		while ( $row = $this->fetch_row( $result ) ) {
			if ( isset( $row[0] ) && ( $table_name = $row[0] ) ) {

				// Include table prefixes
				if ( $this->get_include_table_prefixes() ) {
					$include = false;

					// Check table prefixes
					foreach ( $this->get_include_table_prefixes() as $prefix ) {
						if ( stripos( $table_name, $prefix ) === 0 ) {
							$include = true;
							break;
						}
					}

					// Skip current table
					if ( $include === false ) {
						continue;
					}
				}

				// Exclude table prefixes
				if ( $this->get_exclude_table_prefixes() ) {
					$exclude = false;

					// Check table prefixes
					foreach ( $this->get_exclude_table_prefixes() as $prefix ) {
						if ( stripos( $table_name, $prefix ) === 0 ) {
							$exclude = true;
							break;
						}
					}

					// Skip current table
					if ( $exclude === true ) {
						continue;
					}
				}

				// Add table name
				$tables[] = $table_name;
			}
		}

		// Close result cursor
		$this->free_result( $result );

		return $tables;
	}

	/**
	 * Export database into a file
	 *
	 * @param  string  $file_name    File name
	 * @param  integer $table_index  Table index
	 * @param  integer $table_offset Table offset
	 * @param  integer $table_rows   Table rows
	 * @return boolean
	 */
	public function export( $file_name, &$table_index = 0, &$table_offset = 0, &$table_rows = 0 ) {
		// Set file handler
		$file_handler = shm_open( $file_name, 'ab' );

		// Write headers
		if ( $table_index === 0 ) {
			shm_write( $file_handler, $this->get_header() );
		}

		// Start time
		$start = microtime( true );

		// Flag to hold if all tables have been processed
		$completed = true;

		// Set SQL Mode
		$this->query( "SET SESSION sql_mode = ''" );

		// Get tables
		$tables = $this->get_tables();

		// Export tables
		for ( ; $table_index < count( $tables ); ) {

			// Get table name
			$table_name = $tables[ $table_index ];

			// Replace table name prefixes
			$new_table_name = $this->replace_table_prefixes( $table_name, 0 );

			// Get create table statement
			if ( $table_offset === 0 ) {

				// Write table drop statement
				$drop_table = "\nDROP TABLE IF EXISTS `{$new_table_name}`;\n";

				// Write table statement
				shm_write( $file_handler, $drop_table );

				// Get create table statement
				$create_table = $this->get_create_table( $table_name );

				// Replace create table prefixes
				$create_table = $this->replace_table_prefixes( $create_table, 14 );

				// Replace table constraints
				$create_table = $this->replace_table_constraints( $create_table );

				// Replace create table options
				$create_table = $this->replace_table_options( $create_table );

				// Write table statement
				shm_write( $file_handler, $create_table );

				// Write end of statement
				shm_write( $file_handler, ";\n\n" );
			}

			// Get primary keys
			$primary_keys = $this->get_primary_keys( $table_name );

			// Get table columns
			$table_columns = $this->get_column_types( $table_name );

			// Get prefix columns
			$prefix_columns = $this->get_table_prefix_columns( $table_name );

			do {

				// Set query
				if ( $primary_keys ) {

					// Set table keys
					$table_keys = array();
					foreach ( $primary_keys as $key ) {
						$table_keys[] = sprintf( '`%s`', $key );
					}

					$table_keys = implode( ', ', $table_keys );

					// Set table where clauses
					$table_where = array( 1 );
					foreach ( $this->get_table_where_clauses( $table_name ) as $clause ) {
						$table_where[] = $clause;
					}

					$table_where = implode( ' AND ', $table_where );

					// Set query with offset and rows count
					$query = sprintf( 'SELECT t1.* FROM `%s` AS t1 JOIN (SELECT %s FROM `%s` WHERE %s ORDER BY %s LIMIT %d, %d) AS t2 USING (%s)', $table_name, $table_keys, $table_name, $table_where, $table_keys, $table_offset, SHM_MAX_SELECT_RECORDS, $table_keys );

				} else {

					// Set table keys
					$table_keys = 1;

					// Set table where clauses
					$table_where = array( 1 );
					foreach ( $this->get_table_where_clauses( $table_name ) as $clause ) {
						$table_where[] = $clause;
					}

					$table_where = implode( ' AND ', $table_where );

					// Set query with offset and rows count
					$query = sprintf( 'SELECT * FROM `%s` WHERE %s ORDER BY %s LIMIT %d, %d', $table_name, $table_where, $table_keys, $table_offset, SHM_MAX_SELECT_RECORDS );
				}

				// Run SQL query
				$result = $this->query( $query );

				// Repair table data
				if ( $this->errno() === 1194 ) {

					// Current table is marked as crashed and should be repaired
					$this->repair_table( $table_name );

					// Run SQL query
					$result = $this->query( $query );
				}

				// Generate insert statements
				if ( $num_rows = $this->num_rows( $result ) ) {

					// Loop over table rows
					while ( $row = $this->fetch_assoc( $result ) ) {

						// Write start transaction
						if ( $table_offset % SHM_MAX_TRANSACTION_QUERIES === 0 ) {
							shm_write( $file_handler, "START TRANSACTION;\n" );
						}

						$items = array();
						foreach ( $row as $key => $value ) {
							// Replace table prefix columns
							if ( isset( $prefix_columns[ strtolower( $key ) ] ) ) {
								$value = $this->replace_column_prefixes( $value, 0 );
							}

							$items[] = $this->prepare_table_values( $value, $table_columns[ strtolower( $key ) ] );
						}

						// Set table values
						$table_values = implode( ',', $items );

						// Set insert statement
						$table_insert = "INSERT INTO `{$new_table_name}` VALUES ({$table_values});\n";

						// Write insert statement
						shm_write( $file_handler, $table_insert );

						// Set current table offset
						$table_offset++;

						// Set current table rows
						$table_rows++;

						// Write end of transaction
						if ( $table_offset % SHM_MAX_TRANSACTION_QUERIES === 0 ) {
							shm_write( $file_handler, "COMMIT;\n" );
						}
					}
				} else {

					// Write end of transaction
					if ( $table_offset % SHM_MAX_TRANSACTION_QUERIES !== 0 ) {
						shm_write( $file_handler, "COMMIT;\n" );
					}

					// Set curent table index
					$table_index++;

					// Set current table offset
					$table_offset = 0;
				}

				// Close result cursor
				$this->free_result( $result );

				// Time elapsed
				if ( ( $timeout = apply_filters( 'shm_completed_timeout', 10 ) ) ) {
					if ( ( microtime( true ) - $start ) > $timeout ) {
						$completed = false;
						break 2;
					}
				}
			} while ( $num_rows > 0 );
		}

		// Close file handler
		shm_close( $file_handler );

		return $completed;
	}

	/**
	 * Import database from a file
	 *
	 * @param  string  $file_name    File name
	 * @param  integer $query_offset Query offset
	 * @return boolean
	 */
	public function import( $file_name, &$query_offset = 0 ) {
		// Set max allowed packet
		$max_allowed_packet = $this->get_max_allowed_packet();

		// Set file handler
		$file_handler = shm_open( $file_name, 'r' );

		// Start time
		$start = microtime( true );

		// Flag to hold if all tables have been processed
		$completed = true;

		// Set SQL Mode
		$this->query( "SET SESSION sql_mode = ''" );

		// Set file pointer at the query offset
		if ( fseek( $file_handler, $query_offset ) !== -1 ) {
			$query = null;

			// Start transaction
			$this->query( 'START TRANSACTION' );

			// Read database file line by line
			while ( ( $line = fgets( $file_handler ) ) !== false ) {
				$query .= $line;

				// End of query
				if ( preg_match( '/;\s*$/S', $query ) ) {
					$query = trim( $query );

					// Check max allowed packet
					if ( strlen( $query ) <= $max_allowed_packet ) {

						// Skip cache query
						if ( ! $this->is_cache_query( $query ) ) {

							// Replace table prefixes
							$query = $this->replace_table_prefixes( $query );

							// Replace table collations
							$query = $this->replace_table_collations( $query );

							// Replace table values
							$query = $this->replace_table_values( $query );

							// Replace raw values
							$query = $this->replace_raw_values( $query );

							// Run SQL query
							$this->query( $query );

							// Replace table engines (Azure)
							if ( $this->errno() === 1030 ) {

								// Replace table engines
								$query = $this->replace_table_engines( $query );

								// Run SQL query
								$this->query( $query );
							}

							// Replace table row format (MyISAM and InnoDB)
							if ( $this->errno() === 1071 || $this->errno() === 1709 ) {

								// Replace table row format
								$query = $this->replace_table_row_format( $query );

								// Run SQL query
								$this->query( $query );
							}

							// A statement that still fails would silently drop data: stop and say which one
							if ( $this->errno() ) {
								throw new Shm_Database_Exception(
									sprintf(
										'MySQL error %1$d: %2$s (while running: %3$s)',
										$this->errno(),
										esc_html( $this->error() ),
										esc_html( $this->describe_query( $query ) )
									)
								);
							}
						}

						// Set query offset
						$query_offset = ftell( $file_handler );

						// Time elapsed
						if ( ( $timeout = apply_filters( 'shm_completed_timeout', 10 ) ) ) {
							if ( ! $this->is_atomic_query( $query ) ) {
								if ( ( microtime( true ) - $start ) > $timeout ) {
									$completed = false;
									break;
								}
							}
						}
					}

					$query = null;
				}
			}

			// End transaction
			$this->query( 'COMMIT' );
		}

		// Close file handler
		shm_close( $file_handler );

		return $completed;
	}

	/**
	 * Short printable description of a statement for error messages
	 *
	 * @param  string $query SQL statement
	 * @return string
	 */
	protected function describe_query( $query ) {
		if ( preg_match( '/^\s*([A-Z]+(?:\s+IF\s+(?:NOT\s+)?EXISTS)?(?:\s+(?:INTO|TABLE))?\s+`[^`]+`)/i', $query, $m ) ) {
			return $m[1];
		}

		return substr( preg_replace( '/[^\x20-\x7e]/', '?', $query ), 0, 80 );
	}

	/**
	 * Flush database
	 *
	 * @return void
	 */
	public function flush() {
		foreach ( $this->get_tables() as $table_name ) {
			$this->query( "DROP TABLE IF EXISTS `{$table_name}`" );
		}
	}

	/**
	 * Get MySQL version
	 *
	 * @return string
	 */
	protected function get_version() {
		$result = $this->query( "SHOW VARIABLES LIKE 'version'" );
		$row    = $this->fetch_assoc( $result );

		// Close result cursor
		$this->free_result( $result );

		// Get version
		if ( isset( $row['Value'] ) ) {
			return $row['Value'];
		}
	}

	/**
	 * Get MySQL max allowed packet
	 *
	 * @return integer
	 */
	protected function get_max_allowed_packet() {
		$result = $this->query( "SHOW VARIABLES LIKE 'max_allowed_packet'" );
		$row    = $this->fetch_assoc( $result );

		// Close result cursor
		$this->free_result( $result );

		// Get max allowed packet
		if ( isset( $row['Value'] ) ) {
			return $row['Value'];
		}
	}

	/**
	 * Get MySQL collation name
	 *
	 * @param  string $collation_name Collation name
	 * @return string
	 */
	protected function get_collation( $collation_name ) {
		$result = $this->query( "SHOW COLLATION LIKE '{$collation_name}'" );
		$row    = $this->fetch_assoc( $result );

		// Close result cursor
		$this->free_result( $result );

		// Get collation name
		if ( isset( $row['Collation'] ) ) {
			return $row['Collation'];
		}
	}

	/**
	 * Get MySQL create table
	 *
	 * @param  string $table_name Table name
	 * @return string
	 */
	protected function get_create_table( $table_name ) {
		$result = $this->query( "SHOW CREATE TABLE `{$table_name}`" );
		$row    = $this->fetch_assoc( $result );

		// Close result cursor
		$this->free_result( $result );

		// Get create table
		if ( isset( $row['Create Table'] ) ) {
			return $row['Create Table'];
		}
	}

	/**
	 * Repair MySQL table
	 *
	 * @param  string $table_name Table name
	 * @return void
	 */
	protected function repair_table( $table_name ) {
		$this->query( "REPAIR TABLE `{$table_name}`" );
	}

	/**
	 * Get MySQL primary keys
	 *
	 * @param  string $table_name Table name
	 * @return array
	 */
	protected function get_primary_keys( $table_name ) {
		$primary_keys = array();

		// Get primary keys
		$result = $this->query( "SHOW KEYS FROM `{$table_name}` WHERE `Key_name` = 'PRIMARY'" );
		while ( $row = $this->fetch_assoc( $result ) ) {
			if ( isset( $row['Column_name'] ) ) {
				$primary_keys[] = $row['Column_name'];
			}
		}

		// Close result cursor
		$this->free_result( $result );

		return $primary_keys;
	}

	/**
	 * Get MySQL unique keys
	 *
	 * @param  string $table_name Table name
	 * @return array
	 */
	protected function get_unique_keys( $table_name ) {
		$unique_keys = array();

		// Get unique keys
		$result = $this->query( "SHOW KEYS FROM `{$table_name}` WHERE `Non_unique` = 0" );
		while ( $row = $this->fetch_assoc( $result ) ) {
			if ( isset( $row['Column_name'] ) ) {
				$unique_keys[] = $row['Column_name'];
			}
		}

		// Close result cursor
		$this->free_result( $result );

		return $unique_keys;
	}

	/**
	 * Get MySQL column types
	 *
	 * @param  string $table_name Table name
	 * @return array
	 */
	protected function get_column_types( $table_name ) {
		$column_types = array();

		// Get column types
		$result = $this->query( "SHOW COLUMNS FROM `{$table_name}`" );
		while ( $row = $this->fetch_assoc( $result ) ) {
			if ( isset( $row['Field'] ) ) {
				$column_types[ strtolower( $row['Field'] ) ] = $row['Type'];
			}
		}

		// Close result cursor
		$this->free_result( $result );

		return $column_types;
	}

	/**
	 * Replace table prefixes
	 *
	 * @param  string $input    Table value
	 * @param  mixed  $position Replace first occurrence at a specified position
	 * @return string
	 */
	protected function replace_table_prefixes( $input, $position = false ) {
		// Get table prefixes
		$search  = $this->get_old_table_prefixes();
		$replace = $this->get_new_table_prefixes();

		// Replace first occurance at a specified position
		if ( $position !== false ) {
			for ( $i = 0; $i < count( $search ); $i++ ) {
				$current = stripos( $input, $search[ $i ], $position );
				if ( $current === $position ) {
					$input = substr_replace( $input, $replace[ $i ], $current, strlen( $search[ $i ] ) );
				}
			}

			return $input;
		}

		return str_ireplace( $search, $replace, $input );
	}

	/**
	 * Replace column prefixes
	 *
	 * @param  string $input    Column value
	 * @param  mixed  $position Replace first occurrence at a specified position
	 * @return string
	 */
	protected function replace_column_prefixes( $input, $position = false ) {
		// Get column prefixes
		$search  = $this->get_old_column_prefixes();
		$replace = $this->get_new_column_prefixes();

		// Replace first occurance at a specified position
		if ( $position !== false ) {
			for ( $i = 0; $i < count( $search ); $i++ ) {
				$current = stripos( $input, $search[ $i ], $position );
				if ( $current === $position ) {
					$input = substr_replace( $input, $replace[ $i ], $current, strlen( $search[ $i ] ) );
				}
			}

			return $input;
		}

		return str_ireplace( $search, $replace, $input );
	}

	/**
	 * Replace table values
	 *
	 * @param  string $input Table value
	 * @return string
	 */
	protected function replace_table_values( $input ) {
		// Replace base64 encoded values (Visual Composer)
		if ( $this->get_visual_composer() ) {
			$input = preg_replace_callback( '/\[vc_raw_html\]([a-zA-Z0-9\/+]+={0,2})\[\/vc_raw_html\]/S', array( $this, 'replace_visual_composer_values_callback' ), $input );
		}

		// Replace base64 encoded values (BeTheme Responsive and Optimize Press)
		if ( $this->get_betheme_responsive() || $this->get_optimize_press() ) {
			$input = preg_replace_callback( "/'([a-zA-Z0-9\/+]+={0,2})'/S", array( $this, 'replace_base64_values_callback' ), $input );
		}

		// Replace serialized values
		foreach ( $this->get_old_replace_values() as $old_value ) {
			if ( strpos( $input, $this->escape( $old_value ) ) !== false ) {
				$input = Shm_Database_Utility::map_sql_strings( $input, function ( $matches ) {
					return $this->replace_table_values_callback( $matches );
				} );
				break;
			}
		}

		return $input;
	}

	/**
	 * Replace base64 values callback (Visual Composer)
	 *
	 * @param  array  $matches List of matches
	 * @return string
	 */
	protected function replace_visual_composer_values_callback( $matches ) {
		// Validate base64 data
		if ( Shm_Database_Utility::base64_validate( $matches[1] ) ) {

			// Decode base64 characters
			$matches[1] = Shm_Database_Utility::base64_decode( $matches[1] );

			// Replace values
			$matches[1] = Shm_Database_Utility::replace_values( $this->get_old_replace_values(), $this->get_new_replace_values(), $matches[1] );

			// Encode base64 characters
			$matches[1] = Shm_Database_Utility::base64_encode( $matches[1] );
		}

		return '[vc_raw_html]' . $matches[1] . '[/vc_raw_html]';
	}

	/**
	 * Replace base64 values callback (BeTheme Responsive and Optimize Press)
	 *
	 * @param  array  $matches List of matches
	 * @return string
	 */
	protected function replace_base64_values_callback( $matches ) {
		// Validate base64 data
		if ( Shm_Database_Utility::base64_validate( $matches[1] ) ) {

			// Decode base64 characters
			$matches[1] = Shm_Database_Utility::base64_decode( $matches[1] );

			// Replace serialized values
			$matches[1] = Shm_Database_Utility::replace_serialized_values( $this->get_old_replace_values(), $this->get_new_replace_values(), $matches[1] );

			// Encode base64 characters
			$matches[1] = Shm_Database_Utility::base64_encode( $matches[1] );
		}

		return "'" . $matches[1] . "'";
	}

	/**
	 * Replace table values callback
	 *
	 * @param  array  $matches List of matches
	 * @return string
	 */
	protected function replace_table_values_callback( $matches ) {
		// Unescape MySQL special characters
		$matches[1] = Shm_Database_Utility::unescape_mysql( $matches[1] );

		// Replace serialized values
		$matches[1] = Shm_Database_Utility::replace_serialized_values( $this->get_old_replace_values(), $this->get_new_replace_values(), $matches[1] );

		// Escape MySQL special characters
		$matches[1] = Shm_Database_Utility::escape_mysql( $matches[1] );

		return "'" . $matches[1] . "'";
	}

	/**
	 * Replace table collations
	 *
	 * @param  string $input SQL statement
	 * @return string
	 */
	protected function replace_table_collations( $input ) {
		// DDL only: a row that merely mentions a collation (a blog post, say) must stay as written
		if ( stripos( ltrim( $input ), 'CREATE TABLE' ) !== 0 ) {
			return $input;
		}

		$supported = $this->get_supported_collations();
		if ( ! $supported ) {
			return $input;
		}

		// Server without 4-byte UTF-8 (MySQL < 5.5.3)
		$utf8mb4 = false;
		foreach ( array_keys( $supported ) as $name ) {
			if ( strpos( $name, 'utf8mb4_' ) === 0 ) {
				$utf8mb4 = true;
				break;
			}
		}
		if ( ! $utf8mb4 ) {
			$input = preg_replace( '/\butf8mb4(?=_|\b)/i', 'utf8', $input );
		}

		// MySQL 8.0.30+ spells the 3-byte charset utf8mb3; older servers only know it as utf8
		if ( ! array_filter( array_keys( $supported ), function ( $name ) { return strpos( $name, 'utf8mb3_' ) === 0; } ) ) {
			$input = preg_replace( '/\butf8mb3(?=_|\b)/i', 'utf8', $input );
		}

		// Collations this server does not know (MySQL 8 utf8mb4_0900_*, MariaDB 11 uca1400, ...)
		return preg_replace_callback( '/\b(COLLATE\s*=?\s*|COLLATION\s*=\s*)([A-Za-z0-9_]+)/i', function ( $matches ) use ( $supported ) {
			$name = strtolower( $matches[2] );
			if ( isset( $supported[ $name ] ) ) {
				return $matches[0];
			}

			return $matches[1] . $this->fallback_collation( $name, $supported );
		}, $input );
	}

	/**
	 * Best supported collation of the same character set: unicode_520, then unicode, then general
	 *
	 * @param  string $collation Unsupported collation
	 * @param  array  $supported Supported collations (lower case keys)
	 * @return string
	 */
	protected function fallback_collation( $collation, $supported ) {
		$charset = (string) strstr( $collation, '_', true );
		foreach ( array( '_unicode_520_ci', '_unicode_ci', '_general_ci' ) as $suffix ) {
			if ( isset( $supported[ $charset . $suffix ] ) ) {
				return $charset . $suffix;
			}
		}

		return $collation;
	}

	/**
	 * Collations the connected server knows (lower case keys), cached
	 *
	 * @return array
	 */
	protected function get_supported_collations() {
		if ( $this->supported_collations === null ) {
			$this->supported_collations = array();
			if ( ( $result = $this->query( 'SHOW COLLATION' ) ) ) {
				while ( ( $row = $this->fetch_assoc( $result ) ) ) {
					if ( isset( $row['Collation'] ) ) {
						$this->supported_collations[ strtolower( $row['Collation'] ) ] = true;
					}
				}

				$this->free_result( $result );
			}
		}

		return $this->supported_collations;
	}

	/**
	 * Dry-run an import before it changes anything: every row must fit one packet and every
	 * CREATE TABLE must run on this server (created under a throw-away name, then dropped).
	 * A problem is reported while the site is still exactly as it was.
	 *
	 * @param  string $file_name Path to database.sql
	 * @return void
	 * @throws Shm_Database_Exception
	 */
	public function preflight( $file_name ) {
		$max     = (int) $this->get_max_allowed_packet();
		$handle  = shm_open( $file_name, 'r' );
		$ddl     = null;

		try {
			// Read in small fixed buffers: fgets( $h, $big ) allocates $big bytes on EVERY call
			$length = 0;
			$head   = null;
			while ( ( $chunk = fgets( $handle, 65536 ) ) !== false ) {
				$length += strlen( $chunk );
				if ( $head === null ) {
					$head = $chunk;
				}

				if ( $max > 0 && $length > $max ) {
					$table = preg_match( '/^(?:INSERT INTO|REPLACE INTO) `([^`]+)`/', $head, $m ) ? $this->strip_prefix( $m[1] ) : 'unknown';
					throw new Shm_Database_Exception(
						sprintf(
							'A single row of the table "%1$s" is bigger than this server accepts in one statement (max_allowed_packet = %2$d bytes). ' .
							'Raise max_allowed_packet in the MySQL configuration (my.cnf / my.ini) and try again. Nothing has been changed on your site.',
							esc_html( $table ),
							$max
						)
					);
				}

				// still inside the same (long) line
				if ( substr( $chunk, -1 ) !== "\n" && ! feof( $handle ) ) {
					continue;
				}

				// $head is the start of this line; table definitions are short, so it is the whole line
				if ( $ddl === null && strncmp( $head, 'CREATE TABLE', 12 ) === 0 ) {
					$ddl = '';
				}

				if ( $ddl !== null ) {
					$ddl .= $head;
					if ( preg_match( '/;\s*$/', $head ) ) {
						$this->probe_table( trim( $ddl ) );
						$ddl = null;
					}
				}

				$length = 0;
				$head   = null;
			}
		} finally {
			shm_close( $handle );
		}
	}

	/**
	 * Create one table from the dump under a throw-away name, then drop it
	 *
	 * @param  string $ddl CREATE TABLE statement
	 * @return void
	 * @throws Shm_Database_Exception
	 */
	protected function probe_table( $ddl ) {
		if ( ! preg_match( '/^CREATE TABLE `([^`]+)`/', $ddl, $m ) ) {
			return;
		}

		$name  = $this->strip_prefix( $m[1] );
		$probe = 'shmprobe_' . $name;
		$ddl   = preg_replace( '/^CREATE TABLE `[^`]+`/', 'CREATE TABLE `' . $probe . '`', $ddl, 1 );
		$ddl   = $this->replace_table_constraints( $this->replace_table_collations( $ddl ) );

		$this->query( "DROP TABLE IF EXISTS `{$probe}`" );
		$this->query( $ddl );
		if ( $this->errno() === 1030 ) {
			$this->query( $ddl = $this->replace_table_engines( $ddl ) );
		}
		if ( $this->errno() === 1071 || $this->errno() === 1709 ) {
			$this->query( $ddl = $this->replace_table_row_format( $ddl ) );
		}

		$errno = $this->errno();
		$error = $this->error();
		$this->query( "DROP TABLE IF EXISTS `{$probe}`" );

		if ( $errno ) {
			throw new Shm_Database_Exception(
				sprintf(
					'The table "%1$s" from the backup cannot be created on this database server (MySQL error %2$d: %3$s). Nothing has been changed on your site.',
					esc_html( $name ),
					$errno,
					esc_html( $error )
				)
			);
		}
	}

	/**
	 * Table name without the archive placeholder / any known old prefix
	 *
	 * @param  string $table Table name from the dump
	 * @return string
	 */
	protected function strip_prefix( $table ) {
		$prefixes = array_merge( $this->get_old_table_prefixes(), array( 'SERVMASK_PREFIX_' ) );
		usort( $prefixes, function ( $a, $b ) { return strlen( $b ) - strlen( $a ); } );
		foreach ( $prefixes as $prefix ) {
			if ( $prefix !== '' && strpos( $table, $prefix ) === 0 ) {
				return substr( $table, strlen( $prefix ) );
			}
		}

		return $table;
	}

	/**
	 * Replace raw values
	 *
	 * @param  string $input SQL statement
	 * @return string
	 */
	protected function replace_raw_values( $input ) {
		return Shm_Database_Utility::replace_values( $this->get_old_replace_raw_values(), $this->get_new_replace_raw_values(), $input );
	}

	/**
	 * Replace table constraints
	 *
	 * @param  string $input SQL statement
	 * @return string
	 */
	protected function replace_table_constraints( $input ) {
		$pattern = array(
			'/\s+CONSTRAINT(.+)REFERENCES(.+),/i',
			'/,\s+CONSTRAINT(.+)REFERENCES(.+)/i',
		);

		return preg_replace( $pattern, '', $input );
	}

	/**
	 * Check whether input is transient query
	 *
	 * @param  string  $input SQL statement
	 * @return boolean
	 */
	protected function is_transient_query( $input ) {
		return strpos( $input, "'_transient_" ) !== false;
	}

	/**
	 * Check whether input is site transient query
	 *
	 * @param  string  $input SQL statement
	 * @return boolean
	 */
	protected function is_site_transient_query( $input ) {
		return strpos( $input, "'_site_transient_" ) !== false;
	}

	/**
	 * Check whether input is WooCommerce session query
	 *
	 * @param  string  $input SQL statement
	 * @return boolean
	 */
	protected function is_wc_session_query( $input ) {
		return strpos( $input, "'_wc_session_" ) !== false;
	}

	/**
	 * Check whether input is START TRANSACTION query
	 *
	 * @param  string  $input SQL statement
	 * @return boolean
	 */
	protected function is_start_transaction_query( $input ) {
		return strpos( $input, 'START TRANSACTION' ) === 0;
	}

	/**
	 * Check whether input is COMMIT query
	 *
	 * @param  string  $input SQL statement
	 * @return boolean
	 */
	protected function is_commit_query( $input ) {
		return strpos( $input, 'COMMIT' ) === 0;
	}

	/**
	 * Check whether input is DROP TABLE query
	 *
	 * @param  string  $input SQL statement
	 * @return boolean
	 */
	protected function is_drop_table_query( $input ) {
		return strpos( $input, 'DROP TABLE' ) === 0;
	}

	/**
	 * Check whether input is CREATE TABLE query
	 *
	 * @param  string  $input SQL statement
	 * @return boolean
	 */
	protected function is_create_table_query( $input ) {
		return strpos( $input, 'CREATE TABLE' ) === 0;
	}

	/**
	 * Check whether input is INSERT INTO query
	 *
	 * @param  string  $input      SQL statement
	 * @param  string  $table_name Table name (case insensitive)
	 * @return boolean
	 */
	protected function is_insert_into_query( $input, $table_name ) {
		return stripos( $input, sprintf( 'INSERT INTO `%s`', $table_name ) ) === 0;
	}

	/**
	 * Check whether input is cache query
	 *
	 * @param  string  $input SQL statement
	 * @return boolean
	 */
	public function is_cache_query( $input ) {
		$cache = false;

		// Skip cache based on table query
		switch ( true ) {
			case $this->is_transient_query( $input ):
			case $this->is_site_transient_query( $input ):
			case $this->is_wc_session_query( $input ):
				$cache = true;
				break;
		}

		return $cache;
	}

	/**
	 * Check whether input is atomic query
	 *
	 * @param  string  $input SQL statement
	 * @return boolean
	 */
	protected function is_atomic_query( $input ) {
		$atomic = false;

		// Skip timeout based on table query
		switch ( true ) {
			case $this->is_drop_table_query( $input ):
			case $this->is_create_table_query( $input ):
			case $this->is_start_transaction_query( $input ):
			case $this->is_commit_query( $input ):
				$atomic = true;
				break;

			default:
				// Skip timeout based on table query and table name
				foreach ( $this->get_atomic_tables() as $table_name ) {
					if ( $this->is_insert_into_query( $input, $table_name ) ) {
						$atomic = true;
						break;
					}
				}
		}

		return $atomic;
	}

	/**
	 * Replace table options
	 *
	 * @param  string $input SQL statement
	 * @return string
	 */
	protected function replace_table_options( $input ) {
		// Set table replace options
		$search  = array(
			'TYPE=InnoDB',
			'TYPE=MyISAM',
			'ENGINE=Aria',
			'TRANSACTIONAL=0',
			'TRANSACTIONAL=1',
			'PAGE_CHECKSUM=0',
			'PAGE_CHECKSUM=1',
			'TABLE_CHECKSUM=0',
			'TABLE_CHECKSUM=1',
			'ROW_FORMAT=PAGE',
			'ROW_FORMAT=FIXED',
			'ROW_FORMAT=DYNAMIC',
		);
		$replace = array(
			'ENGINE=InnoDB',
			'ENGINE=MyISAM',
			'ENGINE=MyISAM',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
		);

		return str_ireplace( $search, $replace, $input );
	}

	/**
	 * Replace table engines
	 *
	 * @param  string $input SQL statement
	 * @return string
	 */
	protected function replace_table_engines( $input ) {
		// Set table replace engines
		$search  = array(
			'ENGINE=MyISAM',
			'ENGINE=Aria',
		);
		$replace = array(
			'ENGINE=InnoDB',
			'ENGINE=InnoDB',
		);

		return str_ireplace( $search, $replace, $input );
	}

	/**
	 * Replace table row format
	 *
	 * @param  string $input SQL statement
	 * @return string
	 */
	protected function replace_table_row_format( $input ) {
		// Set table replace row format
		$search  = array(
			'ENGINE=InnoDB',
			'ENGINE=MyISAM',
		);
		$replace = array(
			'ENGINE=InnoDB ROW_FORMAT=DYNAMIC',
			'ENGINE=MyISAM ROW_FORMAT=DYNAMIC',
		);

		return str_ireplace( $search, $replace, $input );
	}

	/**
	 * Returns header for dump file
	 *
	 * @return string
	 */
	protected function get_header() {
		// Some info about software, source and time
		$header = sprintf(
			"-- All In One WP Migration SQL Dump\n" .
			"-- https://servmask.com/\n" .
			"--\n" .
			"-- Host: %s\n" .
			"-- Database: %s\n" .
			"-- Class: %s\n" .
			"--\n",
			$this->wpdb->dbhost,
			$this->wpdb->dbname,
			get_class( $this )
		);

		return $header;
	}

	/**
	 * Prepare table values
	 *
	 * @param  string  $input       Table value
	 * @param  integer $column_type Column type
	 * @return string
	 */
	protected function prepare_table_values( $input, $column_type ) {
		if ( is_null( $input ) ) {
			return 'NULL';
		} elseif ( stripos( $column_type, 'tinyint' ) === 0 ) {
			return $input;
		} elseif ( stripos( $column_type, 'smallint' ) === 0 ) {
			return $input;
		} elseif ( stripos( $column_type, 'mediumint' ) === 0 ) {
			return $input;
		} elseif ( stripos( $column_type, 'int' ) === 0 ) {
			return $input;
		} elseif ( stripos( $column_type, 'bigint' ) === 0 ) {
			return $input;
		} elseif ( stripos( $column_type, 'float' ) === 0 ) {
			return $input;
		} elseif ( stripos( $column_type, 'double' ) === 0 ) {
			return $input;
		} elseif ( stripos( $column_type, 'decimal' ) === 0 ) {
			return $input;
		} elseif ( stripos( $column_type, 'bit' ) === 0 ) {
			return $input;
		}

		return "'" . $this->escape( $input ) . "'";
	}

	/**
	 * Run MySQL query
	 *
	 * @param  string   $input SQL query
	 * @return resource
	 */
	abstract public function query( $input );

	/**
	 * Escape string input for mysql query
	 *
	 * @param  string $input String to escape
	 * @return string
	 */
	abstract public function escape( $input );

	/**
	 * Return the error code for the most recent function call
	 *
	 * @return integer
	 */
	abstract public function errno();

	/**
	 * Return a string description of the last error
	 *
	 * @return string
	 */
	abstract public function error();

	/**
	 * Return server version
	 *
	 * @return string
	 */
	abstract public function version();

	/**
	 * Return the result from MySQL query as associative array
	 *
	 * @param  resource $result MySQL resource
	 * @return array
	 */
	abstract public function fetch_assoc( $result );

	/**
	 * Return the result from MySQL query as row
	 *
	 * @param  resource $result MySQL resource
	 * @return array
	 */
	abstract public function fetch_row( $result );

	/**
	 * Return the number for rows from MySQL results
	 *
	 * @param  resource $result MySQL resource
	 * @return integer
	 */
	abstract public function num_rows( $result );

	/**
	 * Free MySQL result memory
	 *
	 * @param  resource $result MySQL resource
	 * @return boolean
	 */
	abstract public function free_result( $result );
}
