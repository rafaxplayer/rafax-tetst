<?php
/**
 * Plugin Name: Rafax Tests
 * Description: Plugin para crear y gestionar tests con shortcodes.
 * Version: 1.1
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
            <h1>Rafax Tests</h1>
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
                <label for="test_sresults">Mostrar resultados en tiempo real</label>
                <input type="checkbox" name="test_sresults" id="test_sresults" value="1">
                <br><br>
                <label for="test_data">Preguntas y Respuestas (formato JSON,ejem: {
                    "question": "¿Cuál es el resultado de 5 + 3?",
                    "options": ["6", "7", "8", "9"],
                    "correct": 2
                    },)</label>
                <textarea name="test_data" id="test_data" rows="10" cols="50" required></textarea>
                <br><br>
                <button type="submit" class="button button-primary">Guardar Test</button>
            </form>
            <?php
    }
    // rendirzar pagina para editar test
    private function render_edit_test_page($test, $key)
    {

        $rafax_tests_page = esc_url(admin_url('admin.php?page=rafax-tests'));
        $admin_url = esc_url(admin_url('admin-post.php'));
        $show_results_checked = $test['showresults'] === 'yes' ? 'checked' : '';
        ?>
            <div class="rafax-tests">
                <h1>Editar Test</h1>

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
                    <label for="test_sresults">Mostrar resultados en tiempo real</label>
                    <input type="checkbox" name="test_sresults" id="test_sresults" value="" <?php checked($show_results_checked); ?>>
                    <br><br>
                    <label for="test_data">Preguntas y Respuestas (formato JSON,ejem: {
                        "question": "¿Cuál es el resultado de 5 + 3?",
                        "options": ["6", "7", "8", "9"],
                        "correct": 2
                        },)</label>
                    <textarea name="test_data" id="test_data" rows="10" cols="50"
                        required><?php echo esc_textarea($test['data']); ?></textarea>
                    <br><br>
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
        ?>
                <h2>Tests Creados</h2>
                <?php if (!empty($tests)): ?>
                    <table class="widefat fixed">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Shortcode</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tests as $key => $test): ?>
                                <tr>
                                    <td><?php echo esc_html($test['name']); ?></td>
                                    <td>[rafax_test id="<?php echo esc_attr($key); ?>"
                                        items_per_page="<?php echo esc_attr($test['count']); ?>"
                                        show_results="<?php echo esc_attr($test['showresults']); ?>"]</td>
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
                    </table>
                <?php else: ?>
                    <p>No hay tests creados aún.</p>
                <?php endif; ?>
            </div>
            <?php

    }

    //rendirzar css htmls
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
        $test_count = absint($_POST['test_count']);
        $test_sresults = isset($_POST['test_sresults']) ? "yes" : "no";

        // Validar JSON
        $decoded_data = json_decode($test_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die('El formato de las preguntas y respuestas no es válido. Por favor, verifica el JSON.');
        }

        $tests = get_option($this->option_tests_name, []);
        $test_id = uniqid();
        $tests[$test_id] = [
            'name' => $test_name,
            'data' => $test_data,
            'count' => $test_count,
            'showresults' => $test_sresults

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
        $test_count = absint($_POST['test_count']);
        $test_sresults = $test_sresults = isset($_POST['test_sresults']) ? "yes" : "no";

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
                'showresults' => $test_sresults

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
            'show_results' => 'yes'
        ], $atts);

        $test_id = $atts['id'];
        $items_per_page = max(1, (int) $atts['items_per_page']);
        $show_results = $atts['show_results'];

        $tests = get_option($this->option_tests_name, []);
        if (!isset($tests[$test_id])) {
            return 'Test no encontrado';
        }

        // Decodificar JSON y manejar errores
        $test_data = json_decode($tests[$test_id]['data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return 'Datos del test inválidos: ' . json_last_error_msg();
        }

        // Generar identificador único para esta instancia
        $unique_id = uniqid('rafax_test_');

        ob_start();
        ?>
            <div id="<?php echo $unique_id; ?>" class="container">

                <?php if ($show_results == "yes"): ?>
                    <div class="show_results"><img class="result-icon"
                            src="<?php echo plugin_dir_url(__FILE__) . 'img/clock.svg'; ?>"><span id="elapsed-time">00:00:00</span>
                        <img class="result-icon" src="<?php echo plugin_dir_url(__FILE__) . 'img/ok.svg'; ?>"><span
                            id="<?php echo $unique_id; ?>-correct">0</span><img class="result-icon"
                            src="<?php echo plugin_dir_url(__FILE__) . 'img/fail.svg'; ?>"><span
                            id="<?php echo $unique_id; ?>-incorrect">0</span> <img class="result-icon"
                            src="<?php echo plugin_dir_url(__FILE__) . 'img/pages.svg'; ?>"><span
                            id="<?php echo $unique_id; ?>-pages">1/4</span>
                    </div>
                <?php endif; ?>

                <div id="<?php echo $unique_id; ?>-results"></div>
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
                                </label><br>
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
                <div id="<?php echo $unique_id; ?>-result" class="test-result" style="display:none">
                    <h2>Resultado</h2>
                    <p>Total preguntas: <span id="<?php echo $unique_id; ?>-tottal-count">0</span></p>
                    <p><img class="result-icon" src="<?php echo plugin_dir_url(__FILE__) . 'img/clock.svg'; ?>" alt="">Tiempo de
                        transcurrido: <span id="<?php echo $unique_id; ?>-time-count">0</span></p>
                    <p><img class="result-icon" src="<?php echo plugin_dir_url(__FILE__) . 'img/ok.svg'; ?>" alt="">Respuestas
                        correctas: <span id="<?php echo $unique_id; ?>-correct-count">0</span></p>
                    <p><img class="result-icon" src="<?php echo plugin_dir_url(__FILE__) . 'img/fail.svg'; ?>" alt="">Respuestas
                        incorrectas: <span id="<?php echo $unique_id; ?>-incorrect-count">0</span></p>
                </div>
            </div>
            <script>
                jQuery(document).ready(function ($) {

                    const uniqueId = '<?php echo $unique_id; ?>';
                    const itemsPerPage = <?php echo $items_per_page; ?>;
                    const totalQuestions = <?php echo count($test_data); ?>;
                    const totalPages = Math.ceil(totalQuestions / itemsPerPage);
                    let currentPage = 0;
                    const results = Array(totalQuestions).fill(null);
                    const iconOk = '<?php echo plugin_dir_url(__FILE__) . 'img/ok.svg'; ?>';
                    const iconFail = '<?php echo plugin_dir_url(__FILE__) . 'img/fail.svg'; ?>';
                    const showResults = '<?php echo $show_results; ?>';

                    function showPage(page) {
                        $('#<?php echo $unique_id; ?> .question-page').hide();
                        $(`#<?php echo $unique_id; ?> .question-page[data-page="${page}"]`).show();

                        $('#<?php echo $unique_id; ?>-prev-button').toggle(page > 0);
                        $('#<?php echo $unique_id; ?>-next-button').toggle(page < totalPages - 1);
                        $('#<?php echo $unique_id; ?>-result-button').toggle(page === totalPages - 1);
                        $('#<?php echo $unique_id; ?>-repeat-button').toggle(page === totalPages - 1);
                        $('#<?php echo $unique_id; ?>-tottal-quest').text(totalQuestions);
                        $('#<?php echo $unique_id; ?>-pages').text((page + 1) + "/" + totalPages);
                    }

                    $(`#${uniqueId}-next-button`).on('click', function () {
                        if (currentPage < totalPages - 1) {
                            currentPage++;
                            showPage(currentPage);
                        }
                    });

                    $(`#${uniqueId}-prev-button`).on('click', function () {
                        if (currentPage > 0) {
                            currentPage--;
                            showPage(currentPage);
                        }
                    });

                    $(`#${uniqueId}-form input[type="radio"]`).on('change', function () {

                        const questionIndex = $(this).attr('name').split('-')[1];
                        const selectedValue = $(this).val();
                        results[questionIndex] = selectedValue;

                        // Limpiar íconos existentes
                        $(`.question-page[data-page="${Math.floor(questionIndex / itemsPerPage)}"] .answer-option img`).remove();

                        // Validar la respuesta
                        const isCorrect = selectedValue == <?php echo json_encode(array_column($test_data, 'correct')); ?>[questionIndex];

                        // Agregar íconos
                        const icon = isCorrect ? iconOk : iconFail;
                        $(this).parent().append(`<img src="${icon}" alt="${isCorrect ? 'Correcto' : 'Incorrecto'}" class="result-icon">`);

                        // Si es incorrecta, marcar la opción correcta también
                        if (!isCorrect) {
                            const correctAnswer = <?php echo json_encode(array_column($test_data, 'correct')); ?>[questionIndex];
                            $(`input[name="question-${questionIndex}"][value="${correctAnswer}"]`)
                                .parent()
                                .append(`<img src="${iconOk}" alt="Correcto" class="result-icon">`);
                        }
                        // validar attr shortcode para mostrar resultados o no
                        if (showResults == "yes") {
                            let correct = 0, incorrect = 0;

                            <?php foreach ($test_data as $index => $question): ?>
                                if (results[<?php echo $index; ?>] == <?php echo $question['correct']; ?>) {
                                    correct++;
                                } else if (results[<?php echo $index; ?>] !== null) {
                                    incorrect++;
                                }
                            <?php endforeach; ?>

                            $(`#${uniqueId}-correct`).text(correct);
                            $(`#${uniqueId}-incorrect`).text(incorrect);
                        }
                    });

                    $(`#${uniqueId}-result-button`).on('click', function () {
                        let correct = 0, incorrect = 0, tottal = <?php echo count($test_data); ?>;
                        <?php foreach ($test_data as $index => $question): ?>
                            if (results[<?php echo $index; ?>] == <?php echo $question['correct']; ?>) {
                                correct++;
                            } else if (results[<?php echo $index; ?>] !== null) {
                                incorrect++;
                            }
                        <?php endforeach; ?>
                        $(`#${uniqueId}-correct-count`).text(correct);
                        $(`#${uniqueId}-incorrect-count`).text(incorrect);
                        $(`#${uniqueId}-tottal-count`).text(tottal);
                        $(`#${uniqueId}-time-count`).text($(`#${uniqueId} #elapsed-time`).text());
                        $(`#${uniqueId}-result`).show();
                        stopWatch();
                    });

                    $(`#${uniqueId}-repeat-button`).on('click', function () {
                        currentPage = 0;
                        elapsedTime = 0;
                        showPage(0);
                        startStopwatch();
                    });


                    showPage(currentPage);


                    //Timer

                    let stopwatchInterval;
                    let elapsedTime = 0; // Tiempo transcurrido en segundos

                    function updateWatchDisplay() {
                        const hours = Math.floor(elapsedTime / 3600);
                        const minutes = Math.floor((elapsedTime % 3600) / 60);
                        const seconds = elapsedTime % 60;

                        $(`#${uniqueId} #elapsed-time`).text(
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


                    startStopwatch();
                });
            </script>
            <?php
            return ob_get_clean();
    }
}

new RafaxTests();
