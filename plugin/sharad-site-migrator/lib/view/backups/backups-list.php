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

<?php if ( $backups ) : ?>
	<form action="" method="post" id="shm-backups-form" class="shm-clear">
		<table class="shm-backups">
			<thead>
				<tr>
					<th class="shm-column-name"><?php _e( 'Name', SHM_PLUGIN_NAME ); ?></th>
					<th class="shm-column-date"><?php _e( 'Date', SHM_PLUGIN_NAME ); ?></th>
					<th class="shm-column-size"><?php _e( 'Size', SHM_PLUGIN_NAME ); ?></th>
					<th class="shm-column-actions"></th>
				</tr>
			</thead>
			<tbody>
				<tr class="shm-backups-list-spinner-holder shm-hide">
					<td colspan="4" class="shm-backups-list-spinner">
						<span class="spinner"></span>
						<?php _e( 'Refreshing backup list...', SHM_PLUGIN_NAME ); ?>
					</td>
				</tr>

				<?php foreach ( $backups as $backup ) : ?>
				<tr>
					<td class="shm-column-name">
						<?php if ( ! empty( $backup['path'] ) ) : ?>
							<i class="shm-icon-folder"></i>
							<?php echo esc_html( $backup['path'] ); ?>
							<br />
						<?php endif; ?>
						<i class="shm-icon-file-zip"></i>
						<span class="shm-backup-filename">
							<?php echo esc_html( basename( $backup['filename'] ) ); ?>
						</span>
						<span class="shm-backup-label-description shm-hide <?php echo empty( $labels[ $backup['filename'] ] ) ? null : 'shm-backup-label-selected'; ?>">
							<br />
							<?php _e( 'Click to set a label for this backup', SHM_PLUGIN_NAME ); ?>
							<i class="shm-icon-edit-pencil shm-hide"></i>
						</span>
						<span class="shm-backup-label-text <?php echo empty( $labels[ $backup['filename'] ] ) ? 'shm-hide' : null; ?>">
							<br />
							<span class="shm-backup-label-colored">
								<?php if ( ! empty( $labels[ $backup['filename'] ] ) ) : ?>
									<?php echo esc_html( $labels[ $backup['filename'] ] ); ?>
								<?php endif; ?>
							</span>
							<i class="shm-icon-edit-pencil shm-hide"></i>
						</span>
						<span class="shm-backup-label-holder shm-hide">
							<br />
							<input type="text" class="shm-backup-label-field" data-archive="<?php echo esc_attr( $backup['filename'] ); ?>" data-value="<?php echo empty( $labels[ $backup['filename'] ] ) ? null : esc_attr( $labels[ $backup['filename'] ] ); ?>" value="<?php echo empty( $labels[ $backup['filename'] ] ) ? null : esc_attr( $labels[ $backup['filename'] ] ); ?>" />
						</span>
					</td>
					<td class="shm-column-date">
						<?php echo esc_html( sprintf( __( '%s ago', SHM_PLUGIN_NAME ), human_time_diff( $backup['mtime'] ) ) ); ?>
					</td>
					<td class="shm-column-size">
						<?php if ( ! is_null( $backup['size'] ) ) : ?>
							<?php echo shm_size_format( $backup['size'], 2 ); ?>
						<?php else : ?>
							<?php _e( '2GB+', SHM_PLUGIN_NAME ); ?>
						<?php endif; ?>
					</td>
					<td class="shm-column-actions shm-backup-actions">
						<a href="<?php echo esc_url( shm_backup_url( array( 'archive' => $backup['filename'] ) ) ); ?>" class="shm-button-green shm-backup-download" download="<?php echo esc_attr( $backup['filename'] ); ?>" aria-label="<?php _e( 'Download backup', SHM_PLUGIN_NAME ); ?>">
							<i class="shm-icon-arrow-down"></i>
							<span><?php _e( 'Download', SHM_PLUGIN_NAME ); ?></span>
						</a>
						<a href="#" data-archive="<?php echo esc_attr( $backup['filename'] ); ?>" class="shm-button-gray shm-backup-restore" aria-label="<?php _e( 'Restore backup', SHM_PLUGIN_NAME ); ?>">
							<i class="shm-icon-cloud-upload"></i>
							<span><?php _e( 'Restore', SHM_PLUGIN_NAME ); ?></span>
						</a>
						<a href="#" data-archive="<?php echo esc_attr( $backup['filename'] ); ?>" class="shm-button-red shm-backup-delete" aria-label="<?php _e( 'Delete backup', SHM_PLUGIN_NAME ); ?>">
							<i class="shm-icon-close"></i>
							<span><?php _e( 'Delete', SHM_PLUGIN_NAME ); ?></span>
						</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<input type="hidden" name="shm_manual_restore" value="1" />
	</form>
<?php endif; ?>
