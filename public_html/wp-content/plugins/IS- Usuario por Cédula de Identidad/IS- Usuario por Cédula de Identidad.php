<?php
/**
 * Plugin Name: IS- Usuario por Cédula de Identidad
 * Description: Username = Cédula y Password = Cédula. Valida unicidad y agrega campo en admin/registro.
 * Version: 1.1.0
 * Author: Kamuh
 */

if (!defined('ABSPATH')) exit;

/* -------- Registro/Frontend -------- */
add_action('register_form', function () {
    $v = isset($_POST['cedula_ci']) ? esc_attr($_POST['cedula_ci']) : '';
    echo '<p><label for="cedula_ci">Cédula de identidad<br>
          <input type="text" id="cedula_ci" name="cedula_ci" class="input" value="'.$v.'" autocomplete="off"></label></p>';
});

add_filter('registration_errors', function($errors) {
    $ced = isset($_POST['cedula_ci']) ? sanitize_user($_POST['cedula_ci'], true) : '';
    if ($ced === '') $errors->add('cedula_ci', '<strong>Error</strong>: Debe ingresar cédula.');
    if ($ced && username_exists($ced)) $errors->add('cedula_dup', '<strong>Error</strong>: Esa cédula ya existe.');
    return $errors;
}, 10, 1);

add_action('user_register', function($user_id){
    if (empty($_POST['cedula_ci'])) return;
    $ced = sanitize_user($_POST['cedula_ci'], true);
    update_user_meta($user_id, 'cedula_ci', $ced);
    global $wpdb;
    $wpdb->update($wpdb->users, [
        'user_login'    => $ced,
        'user_nicename' => sanitize_title($ced),
    ], ['ID'=>$user_id]);
    wp_set_password($ced, $user_id);
});

/* -------- Admin: Añadir usuario -------- */
add_action('user_new_form', function($type){
    if ($type !== 'add-new-user') return;
    echo '<h3>Identificación</h3><table class="form-table"><tr class="form-field">
          <th><label for="cedula_ci">Cédula de identidad</label></th>
          <td><input type="text" name="cedula_ci" id="cedula_ci" class="regular-text"></td>
          </tr></table>
          <script>(function(){var f=document.getElementById("createuser"); if(!f) return;
          f.addEventListener("submit",function(){var c=document.getElementById("cedula_ci"),u=document.getElementById("user_login");
          if(c&&u) u.value=c.value;});})();</script>';
});

add_action('user_profile_update_errors', function($errors,$update,$user){
    if ($update) return; // solo alta
    $ced = isset($_POST['cedula_ci']) ? sanitize_user($_POST['cedula_ci'], true) : '';
    if ($ced === '') $errors->add('cedula_ci','<strong>Error</strong>: Debe ingresar cédula.');
    if ($ced && username_exists($ced)) $errors->add('cedula_dup','<strong>Error</strong>: Esa cédula ya existe.');
}, 10, 3);

/* -------- Opcional: Ocultar campo user_login en altas -------- */
add_action('admin_head', function(){
    if (get_current_screen() && get_current_screen()->id === 'user') {
        echo '<style>#user_login{display:none}</style>';
    }
});
add_action('login_enqueue_scripts', function(){
    echo '<style>#user_login{display:none}</style>';
});
