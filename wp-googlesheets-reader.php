<?php

/**
 * Plugin Name: WP-Google-Sheets Reader
 * Plugin URI:  https://github.com/gwannon/wp-googlesheets-reader
 * Description: Plugin que lee un Google Sheet y lo muestra como una tabla a través de un shorrtcode. Si modificamos el sheet se modifica el contenido de la tabla en nuestro WordPress. 
 * Version:     1.0
 * Author:      Gwannon
 * Author URI:  https://github.com/gwannon/
 * License:     GNU General Public License v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpgsr
 *
 * PHP 8.2.1
 * WordPress 6.1.1
 */
/*
 * Cómo conectar el plugin con Google Sheets
 * https://www.nidup.io/blog/manipulate-google-sheets-in-php-with-api
 */

/*
 * Uso
 * [sheet spreadsheetId='Se consigue de la URl del documento' sheetName='Nombre de la pestaña que vamos a leer']
 */
 
//Shortcode ------------------
function wpgsrShortcode($params = array(), $content = null) {
  global $post;
  ob_start(); 
  require __DIR__ . '/vendor/autoload.php';
  putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/service_key.json');
  $client = new Google_Client();
  $client->useApplicationDefaultCredentials();
  $client->addScope('https://www.googleapis.com/auth/spreadsheets');
  $service = new Google_Service_Sheets($client);
  //spreadsheetId='1d_Xd87o7zLmnvAE-GyaAjlsOZFMQzrRRbV02yN7Q1Vk'
	$spreadsheet = $service->spreadsheets->get($params['spreadsheetId']);
	//sheetName='Eventos'
	$response = $service->spreadsheets_values->get($params['spreadsheetId'], $params['sheetName']);
	$values = $response->getValues(); ?>
	<table border="1">
		<thead>
			<?php foreach ($values as $count => $tr) {
				if($count == 0) { ?>
					<tr>
						<?php foreach ($tr as $td) { ?><th><?=$td;?></th><?php } ?>
					</tr>
				</thead>
				<tbody>
				<?php } else { ?>
					<tr>
						<?php foreach ($tr as $td) { ?><td><?=$td;?></td><?php } ?>
					</tr>
				<?php }
			} ?>
		</tbody>
	</table>
  <?php return ob_get_clean();
}
add_shortcode('sheet', 'wpgsrShortcode');
