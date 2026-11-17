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

<div class="shm-container">
	<div class="shm-row">
		<div class="shm-left">
			<div class="shm-holder">
				<h1>
					<i class="shm-icon-export"></i>
					<?php _e( 'Backups', SHM_PLUGIN_NAME ); ?>
				</h1>

				<?php if ( is_readable( SHM_BACKUPS_PATH ) && is_writable( SHM_BACKUPS_PATH ) ) : ?>
					<div id="shm-backups-list">
						<?php include SHM_TEMPLATES_PATH . '/backups/backups-list.php'; ?>
					</div>

					<form action="" method="post" id="shm-export-form" class="shm-clear">
						<div id="shm-backups-create">
							<p class="shm-backups-empty-spinner-holder shm-hide">
								<span class="spinner"></span>
								<?php _e( 'Refreshing backup list...', SHM_PLUGIN_NAME ); ?>
							</p>
							<p class="shm-backups-empty <?php echo empty( $backups ) ? null : 'shm-hide'; ?>">
								<?php _e( 'There are no backups available at this time, why not create a new one?', SHM_PLUGIN_NAME ); ?>
							</p>
							<p>
								<a href="#" id="shm-create-backup" class="shm-button-green">
									<i class="shm-icon-export"></i>
									<?php _e( 'Create backup', SHM_PLUGIN_NAME ); ?>
								</a>
							</p>
						</div>
						<input type="hidden" name="shm_manual_export" value="1" />
					</form>

					<?php do_action( 'shm_backups_left_end' ); ?>

				<?php else : ?>

					<?php include SHM_TEMPLATES_PATH . '/backups/backups-permissions.php'; ?>

				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
