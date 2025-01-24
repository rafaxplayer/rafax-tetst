<?php
/**
 * Plugin Name: Rafax Tests
 * Description: Plugin para crear y gestionar tests con shortcodes.
 * Version: 1.2
 * Author: Rafax
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class RafaxTests
{
    private $option_tests_name = 'rafax_tests_data';
    private $option_css_name = 'rafax_tests_css';

    public function __construct()
    {

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_post_save_test', [$this, 'save_test']);
        add_action('admin_post_delete_test', [$this, 'delete_test']);
        add_action('admin_post_edit_test', [$this, 'edit_test']);
        add_action('admin_post_update_test', [$this, 'update_test']);
        add_action('admin_post_save_css', [$this, 'save_css']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_front_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_shortcode('rafax_test', [$this, 'render_test_shortcode']);
        add_action('admin_notices', [$this, 'admin_notices']);
    }
    // Notificaciones
    public function admin_notices()
    {
        if ($notice = get_transient('rafax_admin_notice')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($notice); ?></p>
            </div>
            <?php
            delete_transient('rafax_admin_notice');
        }
    }

    //Front end CSS
    public function enqueue_front_assets()
    {

        wp_enqueue_script('jquery');

        $custom_css = get_option($this->option_css_name, '');

        wp_enqueue_style('rafax-tests-front-css', plugin_dir_url(__FILE__) . 'css/front.css', [], '1.0', 'all');

        // Inyectar CSS personalizado
        if ($custom_css) {
            wp_add_inline_style('rafax-tests-front-css', $custom_css);
        }

    }
    // Admin assets
    public function enqueue_admin_assets($hook)
    {
        if ($hook === 'toplevel_page_rafax-tests') {
            wp_enqueue_style('rafax-tests-admin-css', plugin_dir_url(__FILE__) . 'css/admin.css', [], '1.0', 'all');
            wp_enqueue_script('rafax-tests-admin-js', plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery'], '1.0', true);
        }
    }
    // Admin menu
    public function add_admin_menu()
    {
        add_menu_page('Rafax Tests', 'Rafax Tests', 'manage_options', 'rafax-tests', [$this, 'admin_page'], 'dashicons-edit-large');
    }
    // Admin page
    public function admin_page()
    {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'tests'; ?>

        <div class="wrap">
            <h1>Rafax Tests</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=rafax-tests&tab=tests"
                    class="nav-tab <?php echo $active_tab === 'tests' ? 'nav-tab-active' : ''; ?>">Tests</a>
                <a href="?page=rafax-tests&tab=css"
                    class="nav-tab <?php echo $active_tab === 'css' ? 'nav-tab-active' : ''; ?>">CSS</a>
            </h2>

            <?php
            if ($active_tab === 'tests') {
                $this->tests_page();
            } elseif ($active_tab === 'css') {
                $this->css_page();
            }
            ?>
        </div><?php

    }
    /*TABS*/
    // Tab tests, selector si es para editar o crear test.
    private function tests_page()
    {
        $tests = get_option($this->option_tests_name, []);

        $editid = isset($_GET['edit']) ? sanitize_text_field($_GET['edit']) : null;

        if ($editid && isset($tests[$editid])) {
            $this->render_edit_test_page($tests[$editid], $editid);
        } else {
            $this->render_save_test_page();
        }
        $this->render_list_tests($tests);

    }

    //tab css page
    public function css_page()
    {

        $admin_url = esc_url(admin_url('admin-post.php'));
        $custom_css = get_option($this->option_css_name, '');
        ?>
        <div class="rafax-tests">
            <form method="post" action="<?php echo $admin_url; ?>">
                <input type="hidden" name="action" value="save_css">
                <?php wp_nonce_field('save_css_action', 'save_css_nonce'); ?>
                <h2>Personalizar CSS</h2>
                <textarea name="custom_css" rows="15" cols="70"
                    style="width:100%;"><?php echo esc_textarea($custom_css) ?></textarea>
                <br><br>
                <button type="submit" class="button button-primary">Guardar CSS</button>
            </form>
        </div>
        <?php
    }

    // rendirzar pagina para guardar test
    function render_save_test_page()
    {
        $admin_url = esc_url(admin_url('admin-post.php'));
        ?>
        <div class="rafax-tests">

            <h2>Crear Test</h2>
            <form method="post" action="<?php echo $admin_url; ?>">
                <input type="hidden" name="action" value="save_test">
                <?php wp_nonce_field('save_test_action', 'save_test_nonce'); ?>
                <label for="test_name">Nombre del Test:</label>
                <input type="text" name="test_name" id="test_name" required>
                <br><br>
                <label for="test_count">Nº de Preguntas por pagina</label>
                <input type="number" name="test_count" id="test_count" min="1" value="1">
                <br><br>
                <label for="test_sresults">
                    <input type="checkbox" name="test_sresults" id="test_sresults" value="1">Mostrar resultados en tiempo
                    real</label>
                <br><br>
                <label for="test_is_messages">
                    <input type="checkbox" name="test_is_messages" id="test_is_messages" value="1">Este test dara como
                    resultados un mensaje</label>
                <br><br>
                <label for="test_index">
                    <input type="checkbox" name="test_index" id="test_index" value="1">Mostrar indice de paginas/test</label>
                <br><br>
                <label for="test_style">Estilos</label>
                <select id="test-style" name="test_style">
                    <option value="default">Estilo Predeterminado</option>
                    <option value="dark">Estilo Oscuro</option>
                    <option value="modern">Estilo Moderno</option>
                    <option value="pastel">Estilo Pastel</option>
                    <option value="colored">Estilo Colorido</option>
                    <option value="minimalist">Estilo Minimalista</option>
                </select>
                <br><br>

                <label for="test_data">Preguntas y Respuestas (formato JSON,ejem: {
                    "question": "¿Cuál es el resultado de 5 + 3?",
                    "options": ["6", "7", "8", "9"],
                    "correct": 2
                    },)</label>
                <textarea name="test_data" id="test_data" rows="10" cols="50" required></textarea>
                <br><br>

                <label style="display:none" for="test_messages">Mensajes de respuesta (formato JSON,ejem: {
                    "reply": "El test determina que tienes un comportamiento obsesivo en el trabajo y te aislas de los
                    compañeros ","min":0,"max":20})
                    <textarea name="test_messages" id="test_messages" rows="10" cols="50"></textarea></label>

                <button type="submit" class="button button-primary">Guardar Test</button>
            </form>
            <?php
    }
    // rendirzar pagina para editar test
    private function render_edit_test_page($test, $key)
    {
        $rafax_tests_page = esc_url(admin_url('admin.php?page=rafax-tests'));
        $admin_url = esc_url(admin_url('admin-post.php'));
        $show_results_checked = $test['showresults'] === 'yes';
        $show_messages_checked = $test['showmessages'] === 'yes';
        $show_index_checked = $test['showindex'] === 'yes';
        $test_messages = $test['messages'];
        $test_data = $test['data'];
        $current_style = isset($test['style']) ? $test['style'] : 'default';
        ?>
            <div class="rafax-tests">
                <h2>Editar Test</h2>
                <form method="post" action="<?php echo $admin_url; ?>">
                    <input type="hidden" name="action" value="update_test">
                    <input type="hidden" name="test_id" value="<?php echo esc_attr($key); ?>">
                    <?php wp_nonce_field('update_test_action', 'update_test_nonce'); ?>
                    <label for="test_name">Nombre del Test:</label>
                    <input type="text" name="test_name" id="test_name" value="<?php echo esc_attr($test['name']); ?>" required>
                    <br><br>
                    <label for="test_count">Nº de Preguntas por pagina</label>
                    <input type="number" name="test_count" id="test_count" min="1"
                        value="<?php echo esc_attr($test['count']); ?>">
                    <br><br>
                    <label for="test_sresults">
                        <input type="checkbox" name="test_sresults" id="test_sresults" value="" <?php checked($show_results_checked); ?>>Mostrar resultados en tiempo real</label>
                    <br><br>
                    <label for="test_is_messages">
                        <input type="checkbox" name="test_is_messages" id="test_is_messages" value="" <?php checked($show_messages_checked); ?>>Este test dara como
                        resultados un mensaje</label><br><br>
                    <label for="test_index">
                        <input type="checkbox" name="test_index" id="test_index" value="" <?php checked($show_index_checked); ?>>Mostrar indice de
                        paginas/test</label>
                    <br><br>
                    <select id="test-style" name="test_style">
                        <option value="default" <?php selected($current_style, 'default'); ?>>Estilo Predeterminado</option>
                        <option value="dark" <?php selected($current_style, 'dark'); ?>>Estilo Oscuro</option>
                        <option value="modern" <?php selected($current_style, 'modern'); ?>>Estilo Moderno</option>
                        <option value="pastel" <?php selected($current_style, 'modern'); ?>>Estilo Pastel</option>
                        <option value="colored" <?php selected($current_style, 'colored'); ?>>Estilo Colorido</option>
                        <option value="minimalist" <?php selected($current_style, 'minimalist'); ?>>Estilo Minimalista</option>
                    </select>
                    <label for="test_data">Preguntas y Respuestas (formato JSON,ejem: {
                        "question": "¿Cuál es el resultado de 5 + 3?",
                        "options": ["6", "7", "8", "9"],
                        "correct": 2
                        },)</label>
                    <textarea name="test_data" id="test_data" rows="10" cols="50"
                        required><?php echo esc_textarea($test_data); ?></textarea>
                    <br><br>
                    <label style="display:none" for="test_messages">Mensajes de respuesta (formato JSON,ejem: {
                        "reply": "El test determina que tienes un comportamiento obsesivo en el trabajo y te aislas de los
                        compañeros ",
                        "min":0,"max":20})
                        <textarea name="test_messages" id="test_messages" rows="10"
                            cols="50"><?php echo esc_textarea($test_messages); ?></textarea></label>
                    <button type="submit" class="button button-primary">Actualizar Test</button>
                    <button type="button" class="button button-secondary"
                        onclick="window.location.href='<?php echo $rafax_tests_page; ?>';">Cancelar</button>
                </form>

                <?php
    }
    // rendirzar lista de tests en el admin
    function render_list_tests($tests)
    {
        $admin_url = esc_url(admin_url('admin-post.php'));

        // Configuración de paginación
        $items_per_page = 10; // Número de elementos por página
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1; // Página actual desde la URL
        $total_items = count($tests); // Total de tests
        $total_pages = ceil($total_items / $items_per_page); // Total de páginas

        // Dividir tests según la página actual
        $offset = ($current_page - 1) * $items_per_page;
        $paged_tests = array_slice($tests, $offset, $items_per_page);

        ?>
                <h2>Tests Creados</h2>
                <?php if (!empty($paged_tests)): ?>
                    <table class="widefat fixed">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Shortcode</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paged_tests as $key => $test): ?>
                                <tr>
                                    <td><?php echo esc_html($test['name']); ?></td>
                                    <td class="text_shortcode" data-id="<?php echo esc_attr($key); ?>">
                                        <?php echo sprintf(
                                            '[rafax_test id="%s" items_per_page="%s" show_results="%s" result_messages="%s" show_index ="%s" style="%s"]',
                                            esc_attr($key),
                                            esc_attr($test['count']),
                                            esc_attr($test['showresults']),
                                            esc_attr($test['showmessages']),
                                            esc_attr($test['showindex']),
                                            esc_attr($test['style'])
                                        );
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=rafax-tests&edit=' . esc_attr($key))); ?>"
                                            class="button">Editar</a>
                                        <form method="post" action="<?php echo $admin_url; ?>" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_test">
                                            <input type="hidden" name="test_id" value="<?php echo esc_attr($key); ?>">
                                            <?php wp_nonce_field('delete_test_action', 'delete_test_nonce'); ?>
                                            <button type="submit" class="button button-secondary button-delete"
                                                onclick="return confirm('¿Estás seguro de eliminar este test?');">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <p class="message" style="color: green; display: none;">¡Shortcode copiado!</p>
                    </table>

                    <?php
                    // Mostrar enlaces de paginación
                    $pagination_args = [
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '&paged=%#%',
                        'current' => $current_page,
                        'total' => $total_pages,
                        'prev_text' => __('&laquo; Anterior', 'rafax-cluster'),
                        'next_text' => __('Siguiente &raquo;', 'rafax-cluster'),
                    ];
                    echo '<div class="pagination">';
                    echo paginate_links($pagination_args);
                    echo '</div>';
                    ?>
                <?php else: ?>
                    <p>No hay tests creados aún.</p>
                <?php endif;
    }

    //renderizar css htmls
    public function render_css_page()
    {
        $css_code = get_option($this->option_css_name, '');
        echo '<textarea name="test_css_custom_code" rows="10" cols="50" class="large-text">' . esc_textarea($css_code) . '</textarea>';
    }

    /* Funciones tests css*/
    public function save_css()
    {
        if (!isset($_POST['save_css_nonce']) || !wp_verify_nonce($_POST['save_css_nonce'], 'save_css_action')) {
            wp_die('Nonce verification failed');
        }

        $custom_css = isset($_POST['custom_css']) ? wp_unslash($_POST['custom_css']) : '';
        update_option($this->option_css_name, wp_kses_post($custom_css));
        set_transient('rafax_admin_notice', 'El CSS personalizado se ha guardado correctamente.', 30);

        wp_redirect(admin_url('admin.php?page=rafax-tests&tab=css&message=1'));
        exit;
    }

    /* Funciones tests*/
    // Crear test funcionalidad
    public function save_test()
    {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para hacer esto.');
        }

        check_admin_referer('save_test_action', 'save_test_nonce');

        $test_name = sanitize_text_field($_POST['test_name']);
        $test_data = wp_unslash($_POST['test_data']);
        $test_messages = wp_unslash($_POST['test_messages']);
        $test_count = absint($_POST['test_count']);
        $test_sresults = isset($_POST['test_sresults']) ? "yes" : "no";
        $test_is_messages = isset($_POST['test_is_messages']) ? "yes" : "no";
        $test_index = isset($_POST['test_index']) ? "yes" : "no";
        $test_style = sanitize_text_field($_POST['test_style']);

        // Validar JSON
        $decoded_data = json_decode($test_data, true);
        // si se ha establecido que tenga mensajes
        if ($test_messages) {
            $decoded_messages = json_decode($test_messages, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die('El formato de las preguntas y respuestas o de mensajes no es válido. Por favor, verifica el JSON.');
        }

        $tests = get_option($this->option_tests_name, []);
        $test_id = uniqid();
        $tests[$test_id] = [
            'name' => $test_name,
            'data' => $test_data,
            'messages' => $test_messages,
            'count' => $test_count,
            'showresults' => $test_sresults,
            'showmessages' => $test_is_messages,
            'showindex' => $test_index,
            'style' => $test_style

        ];

        update_option($this->option_tests_name, $tests);
        set_transient('rafax_admin_notice', 'El test se creo correctamente.', 30);

        wp_redirect(admin_url('admin.php?page=rafax-tests'));
        exit;
    }
    // Delete tests funcionalidad
    public function delete_test()
    {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para hacer esto.');
        }

        check_admin_referer('delete_test_action', 'delete_test_nonce');

        $test_id = sanitize_text_field($_POST['test_id']);

        $tests = get_option($this->option_tests_name, []);
        if (isset($tests[$test_id])) {
            unset($tests[$test_id]);
            update_option($this->option_tests_name, $tests);
            set_transient('rafax_admin_notice', 'El test se elimino correctamente.', 30);
        }

        wp_redirect(admin_url('admin.php?page=rafax-tests'));
        exit;
    }
    // Editar test
    public function edit_test()
    {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para hacer esto.');
        }

        $test_id = sanitize_text_field($_GET['edit']);
        $tests = get_option($this->option_tests_name, []);

        if (!isset($tests[$test_id])) {
            wp_redirect(admin_url('admin.php?page=rafax-tests'));
            exit;
        }
    }
    // Actualizar tests funcionalidad
    public function update_test()
    {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para hacer esto.');
        }

        check_admin_referer('update_test_action', 'update_test_nonce');

        $test_id = sanitize_text_field($_POST['test_id']);
        $test_name = sanitize_text_field($_POST['test_name']);
        $test_data = wp_unslash($_POST['test_data']);
        $test_messages = wp_unslash($_POST['test_messages']);
        $test_count = absint($_POST['test_count']);
        $test_sresults = isset($_POST['test_sresults']) ? "yes" : "no";
        $test_is_messages = isset($_POST['test_is_messages']) ? "yes" : "no";
        $test_index = isset($_POST['test_index']) ? "yes" : "no";
        $test_style = sanitize_text_field($_POST['test_style']);

        // Validar JSON
        $decoded_data = json_decode($test_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die('El formato de las preguntas y respuestas no es válido. Por favor, verifica el JSON.');
        }

        $tests = get_option($this->option_tests_name, []);
        if (isset($tests[$test_id])) {
            $tests[$test_id] = [
                'name' => $test_name,
                'data' => $test_data,
                'count' => $test_count,
                'showresults' => $test_sresults,
                'showmessages' => $test_is_messages,
                'showindex' => $test_index,
                'messages' => $test_messages,
                'style' => $test_style

            ];
            update_option($this->option_tests_name, $tests);
            set_transient('rafax_admin_notice', 'El test se actualizado correctamente.', 30);
        } else {
            set_transient('rafax_admin_notice', 'Error: El test no se encontró.', 30);
        }

        wp_redirect(admin_url('admin.php?page=rafax-tests'));
        exit;
    }
    // shortcode
    public function render_test_shortcode($atts)
    {

        $atts = shortcode_atts([
            'id' => '',
            'items_per_page' => 4, // Número predeterminado de preguntas por página
            'show_results' => 'yes',
            'result_messages' => 'no',
            'show_index' => 'no',
            'collapse_test' => 'yes',
            'style' => 'default'
        ], $atts);


        $test_id = $atts['id'];
        $items_per_page = max(1, (int) $atts['items_per_page']);
        $show_results = $atts['show_results'];
        $show_index = $atts['show_index'];
        $collapse_test = $atts['collapse_test'];
        $result_messages = $atts['result_messages'];
        $test_style = $atts['style'] == 'default' ? '' : $atts['style'];
        $icon_ok = plugin_dir_url(__FILE__) . 'img/ok.svg';
        $icon_fail = plugin_dir_url(__FILE__) . 'img/fail.svg';
        $icon_pages = plugin_dir_url(__FILE__) . 'img/pages.svg';


        $tests = get_option($this->option_tests_name, []);

        if (!isset($tests[$test_id])) {
            return 'Test no encontrado';
        }

        // Decodificar JSON y manejar errores
        $test_data = json_decode($tests[$test_id]['data'], true);

        if ($tests[$test_id]['messages']) {

            $test_messages = json_decode($tests[$test_id]['messages'], true);
        }

        if (!$test_data || !is_array($test_data)) {
            return 'Datos del test no válidos o vacíos.';
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            return 'Datos del test inválidos: ' . json_last_error_msg();
        }

        $total_pages = ceil(count($test_data) / $items_per_page);
        // Generar identificador único para esta instancia
        $unique_id = uniqid('rafax_test_');

        ob_start();
        ?>
                <div id="<?php echo $unique_id; ?>" class="<?php echo $test_style; ?> container">

                    <div class="show_results"><svg class="result-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"
                            width="24px" height="24px">
                            <path
                                d="M 16 4 C 9.382813 4 4 9.382813 4 16 C 4 22.617188 9.382813 28 16 28 C 22.617188 28 28 22.617188 28 16 C 28 9.382813 22.617188 4 16 4 Z M 16 6 C 21.535156 6 26 10.464844 26 16 C 26 21.535156 21.535156 26 16 26 C 10.464844 26 6 21.535156 6 16 C 6 10.464844 10.464844 6 16 6 Z M 15 8 L 15 17 L 22 17 L 22 15 L 17 15 L 17 8 Z" />
                        </svg><span id="elapsed-time">00:00:00</span>
                        <?php if ($show_results == "yes" && $result_messages == 'no'): ?>
                            <svg class="result-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="24px"
                                height="24px" baseProfile="basic">
                                <linearGradient id="ONeHyQPNLkwGmj04dE6Soa" x1="16" x2="16" y1="2.888" y2="29.012"
                                    gradientUnits="userSpaceOnUse">
                                    <stop offset="0" stop-color="#36eb69" />
                                    <stop offset="1" stop-color="#1bbd49" />
                                </linearGradient>
                                <circle cx="16" cy="16" r="13" fill="url(#ONeHyQPNLkwGmj04dE6Soa)" />
                                <linearGradient id="ONeHyQPNLkwGmj04dE6Sob" x1="16" x2="16" y1="3" y2="29"
                                    gradientUnits="userSpaceOnUse">
                                    <stop offset="0" stop-opacity=".02" />
                                    <stop offset="1" stop-opacity=".15" />
                                </linearGradient>
                                <path fill="url(#ONeHyQPNLkwGmj04dE6Sob)"
                                    d="M16,3.25c7.03,0,12.75,5.72,12.75,12.75 S23.03,28.75,16,28.75S3.25,23.03,3.25,16S8.97,3.25,16,3.25 M16,3C8.82,3,3,8.82,3,16s5.82,13,13,13s13-5.82,13-13S23.18,3,16,3 L16,3z" />
                                <g opacity=".2">
                                    <linearGradient id="ONeHyQPNLkwGmj04dE6Soc" x1="16.502" x2="16.502" y1="11.26" y2="20.743"
                                        gradientUnits="userSpaceOnUse">
                                        <stop offset="0" stop-opacity=".1" />
                                        <stop offset="1" stop-opacity=".7" />
                                    </linearGradient>
                                    <path fill="url(#ONeHyQPNLkwGmj04dE6Soc)"
                                        d="M21.929,11.26 c-0.35,0-0.679,0.136-0.927,0.384L15,17.646l-2.998-2.998c-0.248-0.248-0.577-0.384-0.927-0.384c-0.35,0-0.679,0.136-0.927,0.384 c-0.248,0.248-0.384,0.577-0.384,0.927c0,0.35,0.136,0.679,0.384,0.927l3.809,3.809c0.279,0.279,0.649,0.432,1.043,0.432 c0.394,0,0.764-0.153,1.043-0.432l6.813-6.813c0.248-0.248,0.384-0.577,0.384-0.927c0-0.35-0.136-0.679-0.384-0.927 C22.608,11.396,22.279,11.26,21.929,11.26L21.929,11.26z" />
                                </g>
                                <path fill="#fff"
                                    d="M10.325,14.825L10.325,14.825c0.414-0.414,1.086-0.414,1.5,0L15,18l6.179-6.179	c0.414-0.414,1.086-0.414,1.5,0l0,0c0.414,0.414,0.414,1.086,0,1.5l-6.813,6.813c-0.478,0.478-1.254,0.478-1.732,0l-3.809-3.809	C9.911,15.911,9.911,15.239,10.325,14.825z" />
                            </svg><span id="<?php echo $unique_id; ?>-correct">0</span><svg class="result-icon"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="24px" height="24px">
                                <path fill="#f44336"
                                    d="M44,24c0,11.045-8.955,20-20,20S4,35.045,4,24S12.955,4,24,4S44,12.955,44,24z" />
                                <path fill="#fff" d="M29.656,15.516l2.828,2.828l-14.14,14.14l-2.828-2.828L29.656,15.516z" />
                                <path fill="#fff" d="M32.484,29.656l-2.828,2.828l-14.14-14.14l2.828-2.828L32.484,29.656z" />
                            </svg><span id="<?php echo $unique_id; ?>-incorrect">0</span>
                        <?php endif; ?>
                       <svg xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://www.w3.org/2000/svg" xmlns:cc="http://web.resource.org/cc/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:sodipodi="http://inkscape.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:svg="http://www.w3.org/2000/svg" xmlns:ns1="http://sozi.baierouge.fr" xmlns:xlink="http://www.w3.org/1999/xlink" id="svg1468" sodipodi:docname="paper4.svg" viewBox="0 0 187.5 187.5" sodipodi:version="0.32" version="1.0" y="0" x="0" inkscape:version="0.42" sodipodi:docbase="C:\Documents and Settings\Jarno\Omat tiedostot\vanhasta\opencliparts\omat\symbols" ><sodipodi:namedview id="base" bordercolor="#666666" inkscape:pageshadow="2" inkscape:window-width="704" pagecolor="#ffffff" inkscape:zoom="1.8346667" inkscape:window-x="0" borderopacity="1.0" inkscape:current-layer="svg1468" inkscape:cx="93.750002" inkscape:cy="93.750002" inkscape:window-y="0" inkscape:window-height="510" inkscape:pageopacity="0.0" /><g id="layer1" ><g id="g2298" transform="matrix(.78306 0 0 .78306 -447.41 -120.69)" ><rect id="rect2296" style="stroke-linejoin:round;stroke:#000000;stroke-linecap:round;stroke-width:3.75;fill:#ffffff" height="146" width="110" y="180.36" x="648" /><rect id="rect2294" style="stroke-linejoin:round;stroke:#000000;stroke-linecap:round;stroke-width:3.75;fill:#ffffff" height="146" width="110" y="198.36" x="634" /><rect id="rect2280" style="stroke-linejoin:round;stroke:#000000;stroke-linecap:round;stroke-width:3.75;fill:#ffffff" height="146" width="110" y="214.36" x="620" /><path id="path2282" style="stroke:#000000;stroke-width:2.6135;fill:none" d="m636.25 258.36h77.5" /><path id="path2284" style="stroke:#000000;stroke-width:2.6135;fill:none" d="m636.25 278.36h77.5" /><path id="path2286" style="stroke:#000000;stroke-width:2.6135;fill:none" d="m636.25 298.36h77.5" /><path id="path2288" style="stroke:#000000;stroke-width:2.6135;fill:none" d="m636.25 318.36h77.5" /><path id="path2290" style="stroke:#000000;stroke-width:2.6135;fill:none" d="m636.25 338.36h77.5" /><path id="path2292" style="stroke:#000000;stroke-width:2.6135;fill:none" d="m636.25 238.36h77.5" /></g ></g ><metadata ><rdf:RDF ><cc:Work ><dc:format >image/svg+xml</dc:format ><dc:type rdf:resource="http://purl.org/dc/dcmitype/StillImage" /><cc:license rdf:resource="http://creativecommons.org/licenses/publicdomain/" /><dc:publisher ><cc:Agent rdf:about="http://openclipart.org/" ><dc:title >Openclipart</dc:title ></cc:Agent ></dc:publisher ><dc:title >Paper 4 icon</dc:title ><dc:date >2006-12-26T00:00:00</dc:date ><dc:description /><dc:source >https://openclipart.org/detail/24793/-by--24793</dc:source ><dc:creator ><cc:Agent ><dc:title >Anonymous</dc:title ></cc:Agent ></dc:creator ></cc:Work ><cc:License rdf:about="http://creativecommons.org/licenses/publicdomain/" ><cc:permits rdf:resource="http://creativecommons.org/ns#Reproduction" /><cc:permits rdf:resource="http://creativecommons.org/ns#Distribution" /><cc:permits rdf:resource="http://creativecommons.org/ns#DerivativeWorks" /></cc:License ></rdf:RDF ></metadata ></svg>
                        <span id="<?php echo $unique_id; ?>-pages">1/4</span>

                    </div>
                    <div class="<?php echo $unique_id; ?>-results"></div>

                    <form id="<?php echo $unique_id; ?>-form">
                        <?php foreach ($test_data as $index => $question): ?>
                            <div class="question-page" data-page="<?php echo floor($index / $items_per_page); ?>"
                                style="display: none;">
                                <h3><?php echo esc_html(((int) $index + 1) . '. ' . $question['question']); ?></h3>
                                <?php foreach ($question['options'] as $option_index => $option): ?>
                                    <label class="answer-option">
                                        <input type="radio" name="question-<?php echo esc_attr($index); ?>"
                                            value="<?php echo esc_attr($option_index); ?>">
                                        <?php echo esc_html($option); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </form>
                    <div class="pagination-controls" ??>
                        <button id="<?php echo $unique_id; ?>-prev-button" class="button-tests" type="button"
                            style="display: none;">Atrás</button>
                        <button id="<?php echo $unique_id; ?>-next-button" class="button-tests" type="button">Siguiente</button>
                        <button id="<?php echo $unique_id; ?>-repeat-button" class="button-tests" type="button"
                            style="display: none;">Repetir
                            test</button>
                        <button id="<?php echo $unique_id; ?>-result-button" type="button" class="button-result"
                            style="display: none;">Mostrar
                            resultado</button>
                    </div>

                    <div id="<?php echo $unique_id; ?>-test-index"
                        style="<?php echo $show_index == 'yes' ? '' : 'display:none'; ?>" class="test-index"><br><br>
                        <p style="flex-basis:100%"><strong>Indice de paginas</strong></p>
                        <?php for ($i = 0; $i < $total_pages; $i++) { ?>
                            <a href=""><?php echo $i + 1 ?></a>

                        <?php } ?>
                    </div>
                    <div id="<?php echo $unique_id; ?>-popup-back" class="popup-back">
                        <div id="<?php echo $unique_id; ?>-result" class="test-result">
                            <h2>Resultado</h2>
                            <div class="result-container">
                                <p>Total preguntas: <span id="<?php echo $unique_id; ?>-tottal-count">0</span></p>
                                <p><svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 32 32" width="24px" height="24px"><path d="M 16 4 C 9.382813 4 4 9.382813 4 16 C 4 22.617188 9.382813 28 16 28 C 22.617188 28 28 22.617188 28 16 C 28 9.382813 22.617188 4 16 4 Z M 16 6 C 21.535156 6 26 10.464844 26 16 C 26 21.535156 21.535156 26 16 26 C 10.464844 26 6 21.535156 6 16 C 6 10.464844 10.464844 6 16 6 Z M 15 8 L 15 17 L 22 17 L 22 15 L 17 15 L 17 8 Z"/></svg>Tiempo de
                                    transcurrido: <span id="<?php echo $unique_id; ?>-time-count">0</span></p>
                                <p><svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 32 32" width="24px" height="24px" baseProfile="basic"><linearGradient id="ONeHyQPNLkwGmj04dE6Soa" x1="16" x2="16" y1="2.888" y2="29.012" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#36eb69"/><stop offset="1" stop-color="#1bbd49"/></linearGradient><circle cx="16" cy="16" r="13" fill="url(#ONeHyQPNLkwGmj04dE6Soa)"/><linearGradient id="ONeHyQPNLkwGmj04dE6Sob" x1="16" x2="16" y1="3" y2="29" gradientUnits="userSpaceOnUse"><stop offset="0" stop-opacity=".02"/><stop offset="1" stop-opacity=".15"/></linearGradient><path fill="url(#ONeHyQPNLkwGmj04dE6Sob)" d="M16,3.25c7.03,0,12.75,5.72,12.75,12.75 S23.03,28.75,16,28.75S3.25,23.03,3.25,16S8.97,3.25,16,3.25 M16,3C8.82,3,3,8.82,3,16s5.82,13,13,13s13-5.82,13-13S23.18,3,16,3 L16,3z"/><g opacity=".2"><linearGradient id="ONeHyQPNLkwGmj04dE6Soc" x1="16.502" x2="16.502" y1="11.26" y2="20.743" gradientUnits="userSpaceOnUse"><stop offset="0" stop-opacity=".1"/><stop offset="1" stop-opacity=".7"/></linearGradient><path fill="url(#ONeHyQPNLkwGmj04dE6Soc)" d="M21.929,11.26 c-0.35,0-0.679,0.136-0.927,0.384L15,17.646l-2.998-2.998c-0.248-0.248-0.577-0.384-0.927-0.384c-0.35,0-0.679,0.136-0.927,0.384 c-0.248,0.248-0.384,0.577-0.384,0.927c0,0.35,0.136,0.679,0.384,0.927l3.809,3.809c0.279,0.279,0.649,0.432,1.043,0.432 c0.394,0,0.764-0.153,1.043-0.432l6.813-6.813c0.248-0.248,0.384-0.577,0.384-0.927c0-0.35-0.136-0.679-0.384-0.927 C22.608,11.396,22.279,11.26,21.929,11.26L21.929,11.26z"/></g><path fill="#fff" d="M10.325,14.825L10.325,14.825c0.414-0.414,1.086-0.414,1.5,0L15,18l6.179-6.179	c0.414-0.414,1.086-0.414,1.5,0l0,0c0.414,0.414,0.414,1.086,0,1.5l-6.813,6.813c-0.478,0.478-1.254,0.478-1.732,0l-3.809-3.809	C9.911,15.911,9.911,15.239,10.325,14.825z"/></svg>Respuestas
                                    correctas: <span id="<?php echo $unique_id; ?>-correct-count">0</span></p>
                                <p><svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 48 48" width="24px" height="24px"><path fill="#f44336" d="M44,24c0,11.045-8.955,20-20,20S4,35.045,4,24S12.955,4,24,4S44,12.955,44,24z"/><path fill="#fff" d="M29.656,15.516l2.828,2.828l-14.14,14.14l-2.828-2.828L29.656,15.516z"/><path fill="#fff" d="M32.484,29.656l-2.828,2.828l-14.14-14.14l2.828-2.828L32.484,29.656z"/></svg>Respuestas
                                    incorrectas: <span id="<?php echo $unique_id; ?>-incorrect-count">0</span></p>
                            </div>
                            <button id="<?php echo $unique_id; ?>-close" class="close-btn">Cerrar</button>
                        </div>
                    </div>
                </div>
                <script>
                    const uniqueId = '#<?php echo $unique_id; ?>';
                    const itemsPerPage = <?php echo $items_per_page; ?>;
                    const testData = JSON.parse('<?php echo json_encode($test_data); ?>');
                    const totalQuestions = <?php echo count($test_data); ?>;
                    const totalPages = <?php echo $total_pages; ?>;
                    const results = Array(totalQuestions).fill(null);
                    const iconOk = '<?php echo $icon_ok ?>';
                    const iconFail = '<?php echo $icon_fail ?>';
                    const messages = JSON.parse('<?php echo json_encode($test_messages); ?>');
                    const showResults = '<?php echo $show_results; ?>';
                    const result_messages = '<?php echo $result_messages; ?>';

                    // Focalizar test al cambiar de pagina
                    function focusTest(element) {
                        $('html, body').animate({
                            scrollTop: $(element).offset().top
                        }, 2000);
                    }
                    // calculo de resultados 
                    function calculateResults(results, testData) {
                        let correct = 0, incorrect = 0;

                        testData.forEach((question, index) => {
                            if (results[index] == question.correct) {
                                correct++;
                            } else if (results[index] !== null) {
                                incorrect++;
                            }
                        });

                        return { correct, incorrect };
                    }

                    //mostrar pagina del test
                    function showPage(page) {

                        $(`${uniqueId} .question-page`).hide();
                        $(`${uniqueId} .question-page[data-page="${page}"]`).show();
                        $(`${uniqueId}-prev-button`).toggle(page > 0);
                        $(`${uniqueId}-next-button`).toggle(page < totalPages - 1);
                        $(`${uniqueId}-result-button`).toggle(page === totalPages - 1);
                        $(`${uniqueId}-repeat-button`).toggle(page === totalPages - 1);
                        $(`${uniqueId}-tottal-quest`).text(totalQuestions);
                        $(`${uniqueId}-pages`).text((page + 1) + "/" + totalPages);

                    }
                    // Manejar mensajes cunado esta la opcion habilitada
                    function getMessage(correctAnswers, totalQuestions, messages) {
                        // Calcula el porcentaje de aciertos
                        const percentage = (correctAnswers / totalQuestions) * 100;

                        // Busca el rango adecuado en los mensajes
                        const message = messages.find(m => percentage >= m.min && percentage <= m.max);

                        // Retorna el mensaje encontrado
                        return message ? message.reply : "No se pudo determinar un mensaje.";
                    }

                    jQuery(document).ready(function ($) {

                        let currentPage = 0;
                        let firstTime = true;


                        $(`${uniqueId}-next-button`).on('click', function () {
                            if (currentPage < totalPages - 1) {
                                currentPage++;
                                showPage(currentPage);
                                focusTest(uniqueId);
                            }

                        });

                        $(`${uniqueId}-prev-button`).on('click', function () {
                            if (currentPage > 0) {
                                currentPage--;
                                showPage(currentPage);
                                focusTest(uniqueId);
                            }

                        });

                        $(`${uniqueId}-form input[type="radio"]`).on('change', function () {

                            const questionIndex = $(this).attr('name').split('-')[1];
                            const selectedValue = $(this).val();
                            results[questionIndex] = selectedValue;

                            // comenzamos el cronometro cuando se seleciona la primera pregunta
                            if (firstTime) {
                                startStopwatch();
                            };
                            firstTime = false;

                            // Limpiar íconos existentes
                            $(`.question-page[data-page="${Math.floor(questionIndex / itemsPerPage)}"] .answer-option img`).remove();

                            // Validar la respuesta
                            const correctAnswer = testData[questionIndex].correct;
                            const isCorrect = selectedValue == correctAnswer;

                            if (showResults == "yes" && result_messages == 'no') {
                                // Agregar íconos
                                const icon = isCorrect ? iconOk : iconFail;
                                $(this).parent().append(`<img src="${icon}" alt="${isCorrect ? 'Correcto' : 'Incorrecto'}" class="result-icon">`);

                                // Si es incorrecta, marcar la opción correcta también
                                if (!isCorrect) {

                                    $(`input[name="question-${questionIndex}"][value="${correctAnswer}"]`)
                                        .parent()
                                        .append(`<img src="${iconOk}" alt="Correcto" class="result-icon">`);
                                }

                                const sclass = isCorrect ? 'correct' : 'incorrect';

                                //limpiar clases
                                $(`.question-page[data-page="${Math.floor(questionIndex / itemsPerPage)}" ] label`).removeClass('incorrect');

                                // añarir classes para algunos stylos (4)
                                $(this).parent().addClass(sclass);

                                const { correct, incorrect } = calculateResults(results, testData)

                                $(`${uniqueId}-correct`).text(correct);
                                $(`${uniqueId}-incorrect`).text(incorrect);
                            }
                        });

                        // logica para el indice de paginas o preguntas
                        $(`${uniqueId}-test-index a`).on('click', function (e) {
                            e.preventDefault();
                            const page = $(this).text();
                            showPage(page - 1);
                            currentPage = page - 1;
                            focusTest(uniqueId);

                        });
                        // logica para mostrar resultado
                        $(`${uniqueId}-result-button`).on('click', function () {

                            const { correct, incorrect } = calculateResults(results, testData)

                            if (result_messages == 'yes') {

                                let message = getMessage(correct, totalQuestions, messages);
                                $(`${uniqueId} .result-container`).html(`<p> ${message} </p>`);

                            } else {
                                $(`${uniqueId}-correct-count`).text(correct);
                                $(`${uniqueId}-incorrect-count`).text(incorrect);
                                $(`${uniqueId}-tottal-count`).text(totalQuestions);
                                $(`${uniqueId}-time-count`).text($(`${uniqueId} #elapsed-time`).text());

                            }

                            $(`${uniqueId}-popup-back`).show();
                            stopWatch();
                        });

                        // close popup de resultados
                        $(`${uniqueId}-popup-back, ${uniqueId}-close`).on('click', function (e) {
                            if (e.target.id === `${uniqueId}-popup-back` || $(e.target).hasClass("close-btn")) {
                                $(`${uniqueId}-popup-back`).fadeOut();
                            }
                        });
                        //Logica para repetir test
                        $(`${uniqueId}-repeat-button`).on('click', function () {
                            location.reload(true);

                        });

                        showPage(currentPage);

                        //Timer
                        let stopwatchInterval;
                        let elapsedTime = 0;

                        function updateWatchDisplay() {
                            const hours = Math.floor(elapsedTime / 3600);
                            const minutes = Math.floor((elapsedTime % 3600) / 60);
                            const seconds = elapsedTime % 60;

                            $(`${uniqueId} #elapsed-time`).text(
                                `${hours.toString().padStart(2, '0')}:` +
                                `${minutes.toString().padStart(2, '0')}:` +
                                `${seconds.toString().padStart(2, '0')}`
                            );
                        }

                        function stopWatch() {

                            clearInterval(stopwatchInterval); // Detiene el cronómetro
                        }

                        function startStopwatch() {
                            updateWatchDisplay();
                            stopwatchInterval = setInterval(function () {
                                elapsedTime++;
                                updateWatchDisplay();
                            }, 1000);
                        }


                    });
                </script>
                <?php
                return ob_get_clean();
    }
}

new RafaxTests();
