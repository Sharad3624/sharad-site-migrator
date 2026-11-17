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

<style type="text/css" media="all">
	@font-face {
		font-family: 'servmask';
		src: url('<?php echo wp_make_link_relative( SHM_URL ); ?>/lib/view/assets/font/servmask.eot?v=<?php echo SHM_VERSION; ?>');
		src: url('<?php echo wp_make_link_relative( SHM_URL ); ?>/lib/view/assets/font/servmask.eot?v=<?php echo SHM_VERSION; ?>#iefix') format('embedded-opentype'),
		url('<?php echo wp_make_link_relative( SHM_URL ); ?>/lib/view/assets/font/servmask.woff?v=<?php echo SHM_VERSION; ?>') format('woff'),
		url('<?php echo wp_make_link_relative( SHM_URL ); ?>/lib/view/assets/font/servmask.ttf?v=<?php echo SHM_VERSION; ?>') format('truetype'),
		url('<?php echo wp_make_link_relative( SHM_URL ); ?>/lib/view/assets/font/servmask.svg?v=<?php echo SHM_VERSION; ?>#servmask') format('svg');
		font-weight: normal;
		font-style: normal;
	}

	[class^="shm-icon-"], [class*=" shm-icon-"] {
		font-family: 'servmask';
		speak: none;
		font-style: normal;
		font-weight: normal;
		font-variant: normal;
		text-transform: none;
		line-height: 1;

		/* Better Font Rendering =========== */
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
	}

	.shm-icon-notification:before {
		content: "\e619";
	}

	.shm-label {
		border: 1px solid #5cb85c;
		background-color: transparent;
		color: #5cb85c;
		cursor: pointer;
		text-transform: uppercase;
		font-weight: 600;
		outline: none;
		transition: background-color 0.2s ease-out;
		padding: .2em .6em;
		font-size: 0.8em;
		border-radius: 5px;
		text-decoration: none !important;
	}

	.shm-label:hover {
		background-color: #5cb85c;
		color: #fff;
	}

	<?php if ( version_compare( $version, '3.8', '<' ) ) : ?>
	.toplevel_page_shm_export > div.wp-menu-image {
		background: none !important;
	}

	.toplevel_page_shm_export > div.wp-menu-image:before {
		line-height: 27px !important;
		content: '';
		background: url('<?php echo wp_make_link_relative( SHM_URL ); ?>/lib/view/assets/img/logo-20x20.png') no-repeat center center;
		speak: none !important;
		font-style: normal !important;
		font-weight: normal !important;
		font-variant: normal !important;
		text-transform: none !important;
		margin-left: 7px;
		/* Better Font Rendering =========== */
		-webkit-font-smoothing: antialiased !important;
		-moz-osx-font-smoothing: grayscale !important;
	}

	<?php else : ?>
	.toplevel_page_shm_export > div.wp-menu-image:before {
		position: relative;
		display: inline-block;
		content: '';
		background: url('<?php echo wp_make_link_relative( SHM_URL ); ?>/lib/view/assets/img/logo-20x20.png') no-repeat center center;
		speak: none !important;
		font-style: normal !important;
		font-weight: normal !important;
		font-variant: normal !important;
		text-transform: none !important;
		line-height: 1 !important;
		/* Better Font Rendering =========== */
		-webkit-font-smoothing: antialiased !important;
		-moz-osx-font-smoothing: grayscale !important;
	}

	.wp-menu-open.toplevel_page_shm_export,
	.wp-menu-open.toplevel_page_shm_export > a {
		background-color: #111 !important;
	}
	<?php endif; ?>
</style>
