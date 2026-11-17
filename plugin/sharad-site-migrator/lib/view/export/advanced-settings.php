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
?>

<div class="shm-field-set">
	<div class="shm-accordion shm-expandable">
		<h4>
			<i class="shm-icon-arrow-right"></i>
			<?php _e( 'Advanced options', SHM_PLUGIN_NAME ); ?>
			<small><?php _e( '(click to expand)', SHM_PLUGIN_NAME ); ?></small>
		</h4>
		<ul>
			<li>
				<label for="shm-no-spam-comments">
					<input type="checkbox" id="shm-no-spam-comments" name="options[no_spam_comments]" />
					<?php _e( 'Do <strong>not</strong> export spam comments', SHM_PLUGIN_NAME ); ?>
				</label>
			</li>
			<li>
				<label for="shm-no-post-revisions">
					<input type="checkbox" id="shm-no-post-revisions" name="options[no_post_revisions]" />
					<?php _e( 'Do <strong>not</strong> export post revisions', SHM_PLUGIN_NAME ); ?>
				</label>
			</li>
			<li>
				<label for="shm-no-media">
					<input type="checkbox" id="shm-no-media" name="options[no_media]" />
					<?php _e( 'Do <strong>not</strong> export media library (files)', SHM_PLUGIN_NAME ); ?>
				</label>
			</li>
			<li>
				<label for="shm-no-themes">
					<input type="checkbox" id="shm-no-themes" name="options[no_themes]" />
					<?php _e( 'Do <strong>not</strong> export themes (files)', SHM_PLUGIN_NAME ); ?>
				</label>
			</li>

			<?php do_action( 'shm_export_inactive_themes' ); ?>

			<li>
				<label for="shm-no-muplugins">
					<input type="checkbox" id="shm-no-muplugins" name="options[no_muplugins]" />
					<?php _e( 'Do <strong>not</strong> export must-use plugins (files)', SHM_PLUGIN_NAME ); ?>
				</label>
			</li>

			<li>
				<label for="shm-no-plugins">
					<input type="checkbox" id="shm-no-plugins" name="options[no_plugins]" />
					<?php _e( 'Do <strong>not</strong> export plugins (files)', SHM_PLUGIN_NAME ); ?>
				</label>
			</li>

			<?php do_action( 'shm_export_inactive_plugins' ); ?>

			<?php do_action( 'shm_export_cache_files' ); ?>

			<li>
				<label for="shm-no-database">
					<input type="checkbox" id="shm-no-database" name="options[no_database]" />
					<?php _e( 'Do <strong>not</strong> export database (sql)', SHM_PLUGIN_NAME ); ?>
				</label>
			</li>
			<li>
				<label for="shm-no-email-replace">
					<input type="checkbox" id="shm-no-email-replace" name="options[no_email_replace]" />
					<?php _e( 'Do <strong>not</strong> replace email domain (sql)', SHM_PLUGIN_NAME ); ?>
				</label>
			</li>

			<?php do_action( 'shm_export_advanced_settings' ); ?>
		</ul>
	</div>
</div>
