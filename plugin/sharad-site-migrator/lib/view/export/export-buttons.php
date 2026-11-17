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
	<div class="shm-buttons">
		<div class="shm-button-group shm-button-export shm-expandable">
			<div class="shm-button-main">
				<span role="list" aria-label="<?php _e( 'Export To', SHM_PLUGIN_NAME ); ?>"><?php _e( 'Export To', SHM_PLUGIN_NAME ); ?></span>
				<span class="ai1mw-lines">
					<span class="shm-line shm-line-first"></span>
					<span class="shm-line shm-line-second"></span>
					<span class="shm-line shm-line-third"></span>
				</span>
			</div>
			<ul class="shm-dropdown-menu shm-export-providers">
				<?php foreach ( apply_filters( 'shm_export_buttons', array() ) as $button ) : ?>
					<li>
						<?php echo $button; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
