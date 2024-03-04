<?php
/**
 * Plugin Name:       Buytiti - Caja Cerrada
 * Plugin URI:        https://buytiti.com
 * Description:       Este plugin genera un formulario en el page caja cerrada y muestra esos datos en el administrador de wordpress.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Jesus Jimenez
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       buytitiformbox
 *
 * @package           buytiti
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function buytitiformbox_buytitiformbox_block_init() {
	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'buytitiformbox_buytitiformbox_block_init' );

// Agrega el formulario al contenido de la página 'http://localhost:8000/caja-cerrada/'
	add_action('the_content', 'mi_plugin_formulario');

	function mi_plugin_formulario($content) {
		if ($_SERVER['REQUEST_URI'] !== '/caja-cerrada/') {
			return $content;
		}
	
		$formulario = '<div class="contenedor-titiform">';
	
		// Mostrar mensaje de correo ya registrado
		$mensaje_error_nombre = '';
		$mensaje_error_email = '';
		$mensaje_error_telefono = '';
		if (isset($_POST['mi_plugin_submit'])) {
			global $wpdb;
			$nombre = sanitize_text_field($_POST['nombre']);
			$email = sanitize_email($_POST['email']);
			$telefono = sanitize_text_field($_POST['telefono']);
			$existing_email = $wpdb->get_var($wpdb->prepare("SELECT email FROM {$wpdb->prefix}mi_plugin_nombres WHERE email = %s", $email));
			$existing_telefono = $wpdb->get_var($wpdb->prepare("SELECT telefono FROM {$wpdb->prefix}mi_plugin_nombres WHERE telefono = %s", $telefono));
	
			// Verificar si el nombre contiene solo espacios en blanco o está vacío
			if (empty(trim($nombre))) {
				$mensaje_error_nombre = '<p style="background-color: red;color: white;font-weight: bold;text-align: center;" class="mensaje-error">Es necesario que pongas tu nombre</p>';
			}
			
			if ($existing_email) {
				$mensaje_error_email = '<p style = "background-color: red;color: white;font-weight: bold;text-align: center;"class="mensaje-error">Este correo ya ha sido registrado</p>';
			}
	
			if ($existing_telefono) {
				$mensaje_error_telefono = '<p style = "background-color: red;color: white;font-weight: bold;text-align: center;"class="mensaje-error">Este número de teléfono ya existe</p>';
			}
		}
	
		$formulario .= '<div class="formulario-cajatiti">
			<form class="form-boxclosed" method="POST" action="' . esc_url($_SERVER['REQUEST_URI']) . '">
			<label>
			Nombre:
			<input type="text" name="nombre" pattern="[^\s].*" title="El nombre no puede comenzar con espacios en blanco." required />
		</label>
				<label>
					Email:
					<input type="email" name="email" required />
					' . $mensaje_error_email . '
				</label>
				<label class="box-tel">
					Teléfono:
					<input type="tel" name="telefono" pattern="[0-9]*" required />
					' . $mensaje_error_telefono . '
				</label>
				<label>
					¿Haz comprado por caja cerrada con nosotros?
					<select class="option-box" name="caja_cerrada" required>
						<option value="">Selecciona una opción</option>
						<option value="Si">Si</option>
						<option value="No">No</option>
					</select>
				</label>
				<input class= "send-input" type="submit" name="mi_plugin_submit" value="Enviar" />
			</form>
		</div>
		<div class="imagen-titiform">
			<img class= "img-titibuy" src="' . esc_url( wp_upload_dir()['baseurl'] . '/titi_caja.png' ) . '" alt="Imagen de Titi Caja">
		</div>
	</div>';
	
		return $content . $formulario;
	}	
		
	// Maneja el envío del formulario y guarda el nombre en la base de datos
add_action('init', 'mi_plugin_guardar_nombre');

function mi_plugin_guardar_nombre() {
    if (isset($_POST['mi_plugin_submit'])) {
        global $wpdb;

        $nombre = trim(sanitize_text_field($_POST['nombre']));
        $email = sanitize_email($_POST['email']);
        $telefono = sanitize_text_field($_POST['telefono']);
        $caja_cerrada = sanitize_text_field($_POST['caja_cerrada']);

        // Verificar si el nombre está vacío después de eliminar los espacios en blanco
        if (empty($nombre)) {
            // Redirige al usuario a la misma página
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }

        // Verificar si el correo electrónico ya existe en la base de datos
        $existing_email = $wpdb->get_var($wpdb->prepare("SELECT email FROM {$wpdb->prefix}mi_plugin_nombres WHERE email = %s", $email));

        // Verificar si el número de teléfono ya existe en la base de datos
        $existing_telefono = $wpdb->get_var($wpdb->prepare("SELECT telefono FROM {$wpdb->prefix}mi_plugin_nombres WHERE telefono = %s", $telefono));

        if ($existing_email || $existing_telefono) {
            // Si el correo o el teléfono ya existen, no realizar la inserción en la base de datos
        } else {
            // Si el correo y el teléfono no existen, realizar la inserción en la base de datos
            $wpdb->insert('wp_mi_plugin_nombres', array(
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'caja_cerrada' => $caja_cerrada,
            ));

            // Redirige al usuario a la misma página
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

	// Agrega una página al menú de administración y muestra los nombres guardados en la base de datos
	add_action('admin_menu', 'mi_plugin_menu');
	
	function mi_plugin_menu() {
		add_menu_page(
			'Caja Cerrada',
			'Caja Cerrada',
			'manage_options',
			'caja-cerrada',
			'mi_plugin_caja_cerrada'
		);
	}
	
	function mi_plugin_caja_cerrada() {
		global $wpdb;
	
		$registros = $wpdb->get_results("SELECT * FROM wp_mi_plugin_nombres");
	
		echo '<table class="tablabuytiti">';
		echo '<tr><th class="buytiti-mx">Nombre</th><th class="buytiti-mx">Email</th><th class="buytiti-mx">Teléfono</th><th class="buytiti-mx">¿Ha comprado por caja cerrada?</th></tr>';
	
		foreach ($registros as $registro) {
			echo '<tr>';
			echo '<td class= "buytiti-cajas">' . esc_html($registro->nombre) . '</td>';
			echo '<td class= "buytiti-cajas">' . esc_html($registro->email) . '</td>';
			echo '<td class= "buytiti-cajas">' . esc_html($registro->telefono) . '</td>';
			echo '<td class= "buytiti-cajas">' . esc_html($registro->caja_cerrada) . '</td>';
			echo '</tr>';
		}
	
		echo '</table>';
		echo '<a class="boton-excell" href="' . esc_url(add_query_arg('descargar_csv', '1')) . '">Descargar CSV</a>';
	}
	// Crea la tabla en la base de datos cuando se activa el plugin
	register_activation_hook(__FILE__, 'mi_plugin_crear_tabla');

	function mi_plugin_crear_tabla() {
		global $wpdb;
	
		$charset_collate = $wpdb->get_charset_collate();
	
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mi_plugin_nombres (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			nombre tinytext NOT NULL,
			email tinytext NOT NULL,
			telefono tinytext NOT NULL,
			caja_cerrada tinytext NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	function mi_plugin_descargar_csv() {
		// Comprueba si el usuario solicitó la descarga del CSV
		if (isset($_GET['descargar_csv'])) {
			global $wpdb;
	
			// Obtiene los registros de la tabla
			$registros = $wpdb->get_results("SELECT * FROM wp_mi_plugin_nombres");
	
			// Define el nombre del archivo CSV
			$nombre_archivo = 'registros.csv';
	
			// Define los encabezados para el archivo CSV
			$encabezados = array('Nombre', 'Email', 'Teléfono', '¿Ha comprado por caja cerrada?');
	
			// Abre el flujo de salida
			$output = fopen('php://output', 'w');
	
			// Envía los encabezados para el archivo CSV
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=' . $nombre_archivo);
	
			// Escribe los encabezados en el archivo CSV
			fputcsv($output, $encabezados);
	
			// Escribe los registros en el archivo CSV
			foreach ($registros as $registro) {
				fputcsv($output, array($registro->nombre, $registro->email, $registro->telefono, $registro->caja_cerrada));
			}
	
			// Cierra el flujo de salida
			fclose($output);
	
			// Termina la ejecución del script
			die();
		}
	}
	add_action('init', 'mi_plugin_descargar_csv');
	

	function mi_plugin_admin_styles() {
		echo '
		<style>
		.tablabuytiti th, .tablabuytiti td {
			border: 1px solid #000;
		}
		.tablabuytiti {
			border-collapse: collapse;  /* Esto asegura que las líneas de las celdas se toquen */
			margin: auto;
			margin-top: 2rem;
		}
		.buytiti-mx{
			width: 16rem !important;
		}
		.boton-excell{
			border: solid 1px green;
			color: white;
			background-color: #75BB64;
			text-decoration: none;
			font-weight: 700;
			height: 1.5rem;
			display: flex;
			width: 8rem;
			text-align: center;
			justify-content: center;
			margin:auto;
			margin-top: .5rem;
			border-radius: 5px;
			align-items: center;
		}
		.buytiti-cajas{
			padding:.3rem;
		}
		.mensaje-error {
			color: white;
			background-color: red !important;
			padding: 5px;
			margin-top: 10px;
		}
		</style>
		';
	}
	add_action('admin_head', 'mi_plugin_admin_styles');
	
?>