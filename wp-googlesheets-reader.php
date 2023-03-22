<?php

/**
 * Plugin Name: WP-Google-Sheets Reader
 * Plugin URI:  https://github.com/gwannon/wp-googlesheets-reader
 * Description: Plugin que lee un Google Sheet y lo muestra como una tabla a través de un shortcode. Si modificamos el sheet se modifica el contenido de la tabla en nuestro WordPress. 
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
 * [sheet spreadsheetid='Se consigue de la URl del documento' sheetname='Nombre de la pestaña que vamos a leer']
 */
 
define('WPGSR_CACHE', 300); //300 segundos 5 minutos

//Shortcode ------------------
function wpgsrShortcode($params = array(), $content = null) {
  ob_start(); 
	$file = plugin_dir_path(__FILE__).'cache/'.$params['spreadsheetid'].'-'.sanitize_title($params['sheetname']).'.html';
	if (file_exists($file)) {
		$diff = time() - filectime($file);
		if ($diff <= WPGSR_CACHE) { //Si es menos de 5 minutos (300 segundos) usamos el cacheo
			return file_get_contents($file);
		} 
	}

	require_once __DIR__ . '/vendor/autoload.php';
  putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/service_key.json');
  $client = new Google_Client();
  $client->useApplicationDefaultCredentials();
  $client->addScope('https://www.googleapis.com/auth/spreadsheets');
  $service = new Google_Service_Sheets($client);

	// This script uses the method of "spreadsheets.get".
	$sheets = $service->spreadsheets->get($params['spreadsheetid'], ["ranges" => [$params['sheetname']], "fields" => "sheets"])->getSheets();
	
	// Following script is a sample script for retrieving "textFormat" and "textFormatRuns".
	$data = $sheets[0]->getData();
	$startRow = $data[0]->getStartRow();
	$startColumn = $data[0]->getStartColumn();
	$rowData = $data[0]->getRowData();
	$res = array();
	foreach ($rowData as $i => $row) {
			$temp = array();
			$control = 0;
			foreach ($row -> getValues() as $j => $value) {
				/*echo "<pre>";
				print_r($value['effectiveFormat']['horizontalAlignment']);
				echo "</pre>";*/
					/*$tempObj = [
						"row" => $i + 1 + $startRow,
						"column" => $j + 1 + $startColumn
					];*/
					if (isset($value['formattedValue']) && $value['formattedValue'] != '') {
							$tempObj['formattedValue'] = $value -> getFormattedValue();
							$control ++;
					} else {
							$tempObj['formattedValue'] = "";
							//continue;
					}
					$userEnteredFormat = $value -> getUserEnteredFormat();
					/*echo "<pre>";
					print_r($userEnteredFormat);
					echo "</pre>";*/
					if (isset($userEnteredFormat['horizontalAlignment'])) {
							$tempObj['horizontalAlignment'] = $userEnteredFormat['horizontalAlignment'];
					} else {
							$tempObj['horizontalAlignment'] = null;
					}
					if (isset($userEnteredFormat['textFormat'])) {
							$tempObj['textFormat'] = $userEnteredFormat -> getTextFormat();
					} else {
							$tempObj['textFormat'] = null;
					}
					if (isset($value['textFormatRuns'])) {
							$tempObj['textFormatRuns'] = $value -> getTextFormatRuns();
					} else {
							$tempObj['textFormatRuns'] = null;
					}
					if ($control > 0) array_push($temp, $tempObj);
			}
			if (count($temp) > 0) array_push($res, $temp);
	}

	/*echo "<pre>";
	print_r($res);
	echo "</pre>";*/
	/*$spreadsheet = $service->spreadsheets->get($params['spreadsheetid'], ['includeGridData' => true]);
	print_r ($spreadsheet);
	$sheet = $spreadsheet->getSheets();
	print_r ($sheet);
	$response = $service->spreadsheets_values->get($params['spreadsheetid'], $params['sheetname'], ['valueRenderOption' => 'FORMATTED_VALUE']);
	$values = $response->getValues();*/ ?>
	<div style="overflow-x:auto;">
		<table border="1">
			<thead>
				<?php foreach ($res as $tr) { ?>
					<tr>
						<?php foreach ($tr as $td) { 
							$tag = (isset($td['textFormat']['bold']) && $td['textFormat']['bold'] == 1 ? "th" : "td");
							$align = (isset($td['horizontalAlignment']) && $td['horizontalAlignment'] != '' ? strtolower($td['horizontalAlignment']) : "left");
							$fontstyle = (isset($td['textFormat']['italic']) && $td['textFormat']['italic'] == 1 ? "italic" : "normal");
							?><<?=$tag?> style="text-align: <?=$align?>; font-style: <?=$fontstyle?>"><?=$td['formattedValue']?></<?=$tag?>><?php 
						} ?>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
  <?php $html = ob_get_clean();
	file_put_contents($file, $html); //Guardamos en cache
	return $html;
}
add_shortcode('sheet', 'wpgsrShortcode');
